<?php
/**
 * Created by PhpStorm.
 * User: Qilong
 * Date: 16/11/13
 * Time: 14:10
 */

class UpdateFundStatisticsCommand extends CConsoleCommand
{
    const FUND_TYPE_ALL     = 'all';    // 全部数据
    const FUND_TYPE_STOCK   = 'gp'; // 股票型基金
    const FUND_TYPE_MIXED   = 'hh'; // 混合型基金
    const FUND_TYPE_BONDS   = 'zq'; // 债券型基金
    const FUND_TYPE_INDEX   = 'zs'; // 指数型基金
    const FUND_TYPE_QDII    = 'qdii'; // QDII基金
    const FUND_TYPE_LOF     = 'lof'; // LOF基金

    const FUND_SUB_TYPE_LONG_TERM_BONDS = 'long term bonds'; // 长期纯债
    const FUND_SUB_TYPE_MIXED_BONDS = 'mixed bonds'; // 混合债基

    const E4 = 10000;

    public function actionGetAllFunds()
    {
        $this->getFundRanking(self::FUND_TYPE_STOCK);
        $this->getFundRanking(self::FUND_TYPE_MIXED);
        $this->getFundRanking(self::FUND_TYPE_BONDS);
        $this->getFundRanking(self::FUND_TYPE_INDEX);
        $this->getFundRanking(self::FUND_TYPE_QDII);
        $this->getFundRanking(self::FUND_TYPE_LOF);
    }

    /**
     * 更新所有基金的指定日期净值
     * @param null $stDate
     */
    public function actionUpdateDailyValue($stDate = null)
    {
        if ($stDate)
        {
            $date = $stDate;
        }
        else if ($stDate == 0)
        {
            $date = null;
        }
        else
        {
            $date = date("Y-m-d", strtotime("-1 days", time()));
        }

        $allFunds = LFundsModel::model()->findAll(['select' => 'code', 'condition' => "code > 000076"]);
        /** @var LFundsModel $fund */
        foreach ($allFunds as $fund)
        {
            $data = $this->getFundValue($fund->code, $date, $date);

            foreach ($data["values"] as $value)
            {
                if (!LFundDailyValuesModel::model()->exists(
                    "fundCode = :code AND valueDate = :valueDate",
                    [":code" => $fund->code, "valueDate" => $value[0]]))
                {
                    $dailyValueModel = new LFundDailyValuesModel;
                    $dailyValueModel->fundCode = $fund->code;
                    $dailyValueModel->valueDate = $value[0];
                    $dailyValueModel->unitValueE4 = $value[1] * self::E4;
                    $dailyValueModel->accumulatedValueE4 = $value[2] * self::E4;
                    $dailyValueModel->dailyIncRateE4 = doubleval($value[3]) * self::E4;
                    $dailyValueModel->buyStatus = $value[4];
                    $dailyValueModel->redeemStatus = $value[5];
                    $dailyValueModel->shareStatus = $value[6];
                    $dailyValueModel->createTime = date("Y-m-d H:i:s");
                    $dailyValueModel->updateTime = date("Y-m-d H:i:s");
                    $dailyValueModel->save();
                }
            }
        }
    }

    /**
     * 获取基金排名（主要是获取全部基金列表）
     * @param $fundType
     * @param null $fundSubType
     * @param null $startDate
     * @param null $endDate
     * @return mixed
     */
    private function getFundRanking($fundType, $fundSubType = null, $startDate = null, $endDate = null)
    {
        $requestUrl = 'http://fund.eastmoney.com/data/rankhandler.aspx?op=ph&dt=kf&rs=&gs=0&sc=zzf&st=desc';
        $params = [
            'ft' => $fundType,
            'sd' => ($startDate == null ? date("Y-m-d") : $startDate),
            'ed' => ($endDate == null ? date("Y-m-d") : $endDate),
            'dx' => '0',    // 0 全部，1 可购买
            'pi' => '1',
            'pn' => '5000', // pi: pageIndex, pn: pageNumber 这里取全部数据不分页
        ];

        $requestUrl .= "&" . http_build_query($params);

        if ($fundType == self::FUND_TYPE_BONDS)
        {
            if ($fundSubType == self::FUND_SUB_TYPE_LONG_TERM_BONDS)
            {
                $requestUrl .= "&qdii=041|&tabSubtype=041,,,,3%E5%B9%B4,";
            }
            else if ($fundSubType == self::FUND_SUB_TYPE_MIXED_BONDS)
            {
                $requestUrl .= "&qdii=043|&tabSubtype=043,,,,3%E5%B9%B4,";
            }
            else
            {
                $requestUrl .= "&qdii=|&tabSubtype=,,,,3%E5%B9%B4,";
            }
        }
        else if ($fundType == self::FUND_TYPE_QDII)
        {
            $requestUrl .= "&qdii=&tabSubtype=043,,,,3%E5%B9%B4,";
        }
        else
        {
            $requestUrl .= "&qdii=|&tabSubtype=,,,,3%E5%B9%B4,";
        }

        $ch = curl_init($requestUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $ret = curl_exec($ch);

        curl_close($ch);

        $result = substr($ret, 15);
        $result = rtrim($result, ";");

        $result = preg_replace('/(\w+):(\[.*\]|\d+)/', '"$1":$2', $result);

        $funds = json_decode($result, true);

        foreach ($funds['datas'] as $fund)
        {
            list($code, $name) = explode(",", $fund);

            /** @var LFundsModel $fundModel */
            $fundModel = LFundsModel::model()->findByPk($code);

            if ($fundModel)
            {
                if ($fundModel->name != $name)
                {
                    print_r("update fund code[{$code}] oriName[{$fundModel->name}] newName[{$name}] \n");
                    $fundModel->name = $name;
                    $fundModel->updateTime = date("Y-m-d H:i:s");
                    $fundModel->save();
                }
            }
            else
            {
                $fundModel = new LFundsModel;
                $fundModel->code = $code;
                $fundModel->name = $name;
                $fundModel->createTime = date("Y-m-d H:i:s");
                $fundModel->updateTime = date("Y-m-d H:i:s");
                $fundModel->save();
                print_r("insert fund code[{$code}] name[{$name}] \n");
            }
        }
    }


    /**
     * 获取基金净值
     * @param $fundCode
     * @param null $startDate
     * @param null $endDate
     * @return array
     */
    function getFundValue($fundCode, $startDate = null, $endDate = null)
    {
        // http://fund.eastmoney.com/f10/F10DataApi.aspx?type=lsjz&code=320011&per=100000&page=1&per=20&sdate=2016-08-02&edate=2016-08-10
        $requestUrl = "http://fund.eastmoney.com/f10/F10DataApi.aspx?";
        $param = [
            "type" => "lsjz",
            "code" => $fundCode,
            "per" => 10000000
        ];

        if ($startDate !== null)
        {
            $param["sdate"] = $startDate;
        }

        if ($endDate !== null)
        {
            $param["edate"] = $endDate;
        }

        $requestUrl .= http_build_query($param);

        print_r($requestUrl . "\n");

        $ch = curl_init($requestUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $ret = curl_exec($ch);

        curl_close($ch);

        $result = $ret;// mb_convert_encoding($ret, "utf-8", "GBK");

        // 处理返回数据，提取出净值部分, 是个html页面片段
        $content = preg_replace('/.*"(.*)".*/', "$1", $result);

        $xmlObj = simplexml_load_string($content);

        $header = [];
        foreach ($xmlObj->thead->tr->th as $values)
        {
            $header[] = $values->__toString();
        }

        $values = [];

        foreach ($xmlObj->tbody->tr as $value)
        {
            $tmp = [];
            foreach ($value->td as $field)
            {
                $tmp[] = $field->__toString();
            }
            $values[] = $tmp;
        }

        return [
            "header" => $header,
            "values" => $values
        ];
    }
}