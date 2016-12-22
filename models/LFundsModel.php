<?php

/**
 * @property string code
 * @property string name
 * @property string createTime
 * @property string updateTime
 */
class LFundsModel extends CActiveRecord
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
        return "funds";
    }
}
