<?php
/**
 * NSActiveRecord class file.
 * @author Christoffer Niska <christoffer.niska@nordsoftware.com>
 * @copyright Copyright &copy; Nord Software 2013-
 * @license http://www.opensource.org/licenses/bsd-license New BSD License
 * @version 1.0.0
 */

/**
 * The following properties are available for this model:
 * @property string created
 * @property string updated
 * @property string deleted
 * @property integer $status
 */
abstract class NSActiveRecord extends CActiveRecord {
	// Active record statuses
	const STATUS_DELETED = -1;
	const STATUS_DEFAULT = 0;

	private $_deleted = false;

	public function behaviors() {
		return array(
			'formatter' => array(
				'class' => 'vendor.crisu83.yii-formatter.behaviors.FormatterBehavior',
				'formatters' => array(
					'dateTime' => array('dateWidth' => 'short', 'timeWidth' => 'short'),
				),
			),
		);
	}

	/**
	 * Returns the default named scope that should be implicitly applied to all queries for this model.
	 * @return array the query criteria.
	 */
	public function defaultScope() {
		$scope = parent::defaultScope();
		if ($this->hasAttribute('status')) {
			$tableAlias = $this->getTableAlias(true, false/* do not check scopes */);
			$condition = $tableAlias . '.status>=0';
			$scope['condition'] = isset($scope['condition'])
				? '(' . $scope['condition'] . ') AND (' . $condition . ')'
				: $condition;
		}
		return $scope;
	}

	/**
	 * This method is invoked before saving a record (after validation, if any).
	 * @return boolean whether the saving should be executed. Defaults to true.
	 */
	protected function beforeSave() {
		if (parent::beforeSave()) {
			if ($this->isNewRecord) {
				if ($this->hasAttribute('created')) {
					$this->created = new CDbExpression('NOW()');
				}
			} else {
				unset($this->created); // make sure that we do not change these
				if (!$this->_deleted && $this->hasAttribute('updated')) {
					$this->updated = new CDbExpression('NOW()');
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * This method is invoked before deleting a record.
	 * @return boolean whether the record should be deleted. Defaults to true.
	 */
	protected function beforeDelete() {
		if (parent::beforeDelete()) {
			if ($this->hasAttribute('deleted')) {
				$this->deleted = new CDbExpression('NOW()');
			}
			if ($this->hasAttribute('status')) {
				$this->status = self::STATUS_DELETED;
			}
			$this->_deleted = true;
			$this->save(false);
			return false; // Prevent actual DELETE query from being run
		}
		return true;
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'created' => Yii::t('label', 'Created'),
			'updated' => Yii::t('label', 'Last edit'),
			'deleted' => Yii::t('label', 'Deleted'),
			'status' => Yii::t('label', 'Status'),
		);
	}

	/**
	 * Return basic select options for the record.
	 * @return array the options.
	 */
	public static function getSelectOptions() {
		return CHtml::listData(static::model()->findAll(), 'id', 'name');
	}

	public function getFormatter() {
		return $this->asa('NSFormatter');
	}
}
