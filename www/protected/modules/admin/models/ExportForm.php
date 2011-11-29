<?php

/**
 * ImportZipForm class.
 * ImportZipForm is the data structure for uploading a zip file to import into the system
 */
class ExportForm extends CFormModel
{
  public $filename;
  public $imageSets;
  public $tags;
  public $tags_search_option = 'OR';
  public $players;
  public $players_search_option = 'OR';
  
  public $tag_weight_min = 1;
  public $tag_weight_sum = 10;
  
  public $created_after;
  public $created_before;
  
  public $option_list_user = 0;
  
  public $affected_images = array();
  public $active_image = 0;
  /**
   * Declares the validation rules.
   */
  public function rules()
  {
    return array(
      array('filename, tag_weight_min, tag_weight_sum', 'required'),
      array('tag_weight_min, tag_weight_sum', 'numerical', 'min'=>0),
      array('created_after, created_before', 'date', 'format' => array('yyyy-mm-dd hh:mm:ss', 'yyyy-mm-dd hh:mm', 'yyyy-mm-dd', 'yyyy-mm', 'yyyy')),
      array('imageSets, tags, players, tags_search_option, players_search_option, created_after, created_before, option_list_user, active_image, affected_images', 'safe'),
    );
  }

  /**
   * Declares customized attribute labels.
   * If not declared here, an attribute would have a label that is
   * the same as its name with the first letter in upper case.
   */
  public function attributeLabels()
  {
    return array(
      'filename'=> Yii::t('app', "Export File Name"),
      'imageSets' => Yii::t('app', "Image Sets(s)"),
      'tags'=> Yii::t('app', "Tag(s)"),
      'players'=> Yii::t('app', "Player Name(s)"),
      'tag_weight_min'=> Yii::t('app', "Tag Weight (minimum)"),
      'tag_weight_sum'=> Yii::t('app', "Tag Weight (sum)"),
      'created_after'=> Yii::t('app', "Submitted (from)"),
      'created_before'=> Yii::t('app', "Submitted (until)"),
      'option_list_user' => Yii::t('app', "List Player Names"),
    );
  }

}