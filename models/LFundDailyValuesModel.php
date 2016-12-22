<?php

/**
 * @property string fundCode
 * @property string valueDate
 * @property string unitValueE4
 * @property string accumulatedValueE4
 * @property string dailyIncRateE4
 * @property string buyStatus
 * @property string redeemStatus
 * @property string shareStatus
 * @property string createTime
 * @property string updateTime
 */
class LFundDailyValuesModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return CActiveRecord
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * framework
     * @return string
     */
    public function tableName()
    {
        return "fund_daily_values";
    }
}
