<?php

/**
 * This is the model base class for the table "user_game_bookmark".
 * DO NOT MODIFY THIS FILE! It is automatically generated by giix.
 * If any changes are necessary, you must set or override the required
 * property or method in class "UserGameBookmark".
 *
 * Columns in table "user_game_bookmark" available as properties of the model,
 * followed by relations of table "user_game_bookmark" available as properties of the model.
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $game_id
 * @property integer $media_id
 * @property integer $played_game_id
 * @property string $created
 *
 * @property User $user
 * @property Game $game
 * @property Media $media
 * @property PlayedGame $playedGame
 */
abstract class BaseUserGameBookmark extends GxActiveRecord {

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'user_game_bookmark';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'UserGameBookmark|UserGameBookmarks', $n);
	}

	public static function representingColumn() {
		return 'created';
	}

	public function rules() {
		return array(
			array('user_id, game_id, media_id, created', 'required'),
			array('user_id, game_id, media_id, played_game_id', 'numerical', 'integerOnly'=>true),
			array('played_game_id', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, user_id, game_id, media_id, played_game_id, created', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'user' => array(self::BELONGS_TO, 'User', 'user_id'),
			'game' => array(self::BELONGS_TO, 'Game', 'game_id'),
			'media' => array(self::BELONGS_TO, 'Media', 'media_id'),
			'playedGame' => array(self::BELONGS_TO, 'PlayedGame', 'played_game_id'),
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'id' => Yii::t('app', 'ID'),
			'user_id' => null,
			'game_id' => null,
			'media_id' => null,
			'played_game_id' => null,
			'created' => Yii::t('app', 'Created'),
			'user' => null,
			'game' => null,
			'media' => null,
			'playedGame' => null,
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('user_id', $this->user_id);
		$criteria->compare('game_id', $this->game_id);
		$criteria->compare('media_id', $this->media_id);
		$criteria->compare('played_game_id', $this->played_game_id);
		$criteria->compare('created', $this->created, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination'=>array(
        'pageSize'=>Yii::app()->fbvStorage->get("settings.pagination_size"),
      ),
		));
	}
}