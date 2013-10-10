<?php


Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('media-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<?php echo GxHtml::link(Yii::t('app', 'Advanced Search'), '#', array('class' => 'search-button')); ?>
<div class="search-form">
    <?php $this->renderPartial('_search', array(
    'model' => $model,
)); ?>
</div><!-- search-form -->

<?php echo CHtml::beginForm('', 'post', array('id' => 'media-form'));
$tagDialog = $this->widget('MGTagJuiDialog');

// Maximum number of tags to show in the 'Top Tags' column.
$max_toptags = 15;

function generateImage($data) {
    $media = CHtml::image(MGHelper::getMediaThumb($data->institution->url,$data->mime_type,$data->name),$data->name) . " <span>" . $data->name . "</span>";
    return $media;
}

$this->widget('zii.widgets.grid.CGridView', array(
    'id' => 'media-grid',
    'dataProvider' => $model->search(),
    'filter' => $model,
    'cssFile' => Yii::app()->request->baseUrl . "/css/yii/gridview/styles.css",
    'pager' => array('cssFile' => Yii::app()->request->baseUrl . "/css/yii/pager.css"),
    'baseScriptUrl' => Yii::app()->request->baseUrl . "/css/yii/gridview",
    'afterAjaxUpdate' => $tagDialog->gridViewUpdate(),
    'selectableRows' => 2,
    'columns' => array(
        array(
            'name' => 'name',
            'cssClassExpression' => '"media"',
            'type' => 'html',
            'value' => 'generateImage($data)',
        ),
        'tag_count',
        array(
            'cssClassExpression' => "'tags'",
            'header' => Yii::t('app', "Top $max_toptags Tags"),
            'type' => 'html',
            'value' => '$data->getTopTags(' . $max_toptags . ')',
        ),
        array(
            'cssClassExpression' => "'tags'",
            'header' => Yii::t('app', 'Collections'),
            'type' => 'html',
            'value' => '$data->listCollections()',
        ),
        array(
            'cssClassExpression' => "'tags'",
            'header' => Yii::t('app', 'Institution'),
            'type' => 'html',
            'value' => '$data->institution->name',
        ),
       ),
));
echo CHtml::endForm();

?>