<?php
/**
 * This file is part of P4A - PHP For Applications.
 *
 * P4A is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * P4A is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with Foobar.  If not, see <http://www.gnu.org/licenses/agpl.html>.
 * 
 * To contact the authors write to:									<br />
 * CreaLabs SNC														<br />
 * Via Medail, 32													<br />
 * 10144 Torino (Italy)												<br />
 * Website: {@link http://www.crealabs.it}							<br />
 * E-mail: {@link mailto:info@crealabs.it info@crealabs.it}
 *
 * @author Andrea Giardina <andrea.giardina@crealabs.it>
 * @author Fabrizio Balliano <fabrizio.balliano@crealabs.it>
 * @copyright CreaLabs SNC
 * @link http://www.crealabs.it
 * @link http://p4a.sourceforge.net
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 * @package p4a
 */

/**
 * Tabular rapresentation of a data source.
 * This is a complex widget that's used to allow users to navigate
 * data sources and than (for example) edit a record or view details etc...
 * @author Andrea Giardina <andrea.giardina@crealabs.it>
 * @author Fabrizio Balliano <fabrizio.balliano@crealabs.it>
 * @copyright CreaLabs SNC
 * @package p4a
 */
class P4A_Table extends P4A_Widget
{
	/**
	 * Data source associated with the table
	 * @var P4A_Data_Source
	 */
	public $data = null;

	/**
	 * The gui widgets to allow table navigation
	 * @var P4A_Table_Navigation_Bar
	 */
	public $navigation_bar = null;

	/**
	 * The table toolbar
	 * @var P4A_Toolbar
	 */
	public $toolbar = null;

	/**
	 * All the table's rows
	 * @var P4A_Table_Rows
	 */
	public $rows = null;

	/**
	 * Decides if the table will show the "field's header" row
	 * @var boolean
	 */
	protected $_show_headers = true;

	/**
	 * Stores the table's structure (table_cols)
	 * @var array
	 */
	public $cols = array();

	/**
	 * Displaying order of columns
	 * @var array
	 */
	protected $_cols_order = array();

	/**
	 * A title (caption) for the table
	 * @var string
	 */
	protected $_title = "";

	/**
	 * Automatically add the navigation bar?
	 * @var boolean
	 */
	protected $_auto_navigation_bar = true;

	/**
	 * Wich page is shown?
	 * @var integer
	 */
	protected $_current_page_number = 1;

	/**
	 * @param string $name Mnemonic identifier for the object
	 */
	public function __construct($name)
	{
		parent::__construct($name);
		$this->build('p4a_table_rows', 'rows');
		$this->build('p4a_table_navigation_bar', 'navigation_bar');
		$this->useTemplate('table');
	}

	/**
	 * Sets the data source that the table will navigate
	 * @param P4A_Data_Source $data_source
	 * @return P4A_Table
	 */
	public function setSource(P4A_Data_Source $data_source)
	{
		$this->data = $data_source;
		$this->setDataStructure($this->data->fields->getNames());
		return $this;
	}

	/**
	 * Sets the table's structure (fields)
	 * @param array $array_fields
	 * @return P4A_Table
	 */
	public function setDataStructure(array $array_fields)
	{
		$this->build('p4a_collection', 'cols');
		foreach($array_fields as $field) {
			$this->addCol($field);
		}
		return $this;
	}

	/**
	 * Adds a column to the data structure
	 * @param string $column_name
	 * @return P4A_Table
	 */
	protected function addCol($column_name)
	{
		$this->cols->build('p4a_table_col', $column_name);
		if (!empty($this->_cols_order)) {
			$this->_cols_order[] = $column_name;
		}
		return $this;
	}

	/**
	 * Adds a special clickable column
	 * @param string $column_name
	 * @return P4A_Table
	 */
	public function addActionCol($column_name)
	{
		$this->addCol($column_name);
		$this->cols->$column_name->setType('action');
		$this->cols->$column_name->addAction('onclick');
		return $this;
	}

	/**
	 * Returns the HTML rendered object
	 * @return string
	 */
	public function getAsString()
	{
		$id = $this->getId();
		if (!$this->isVisible()) {
			return "<div id='$id' class='hidden'></div>";
		}

		// if for some reason this page is empty we go back to page one
		$num_page = $this->getCurrentPageNumber();
		$rows = $this->data->page($num_page, false);
		if (empty($rows)) {
			$num_page = 1;
			$this->setCurrentPageNumber($num_page);
			$rows = $this->data->page($num_page, false);
		}

		if ($this->toolbar !== null and
			$this->toolbar->isVisible()) {
			$this->addTempVar('toolbar', $this->toolbar->getAsString());
		}

		if ($this->navigation_bar !== null) {
			if ($this->_auto_navigation_bar) {
				if ($this->data->getNumPages() > 1) {
					$this->addTempVar('navigation_bar', $this->navigation_bar->getAsString());
				}
			} else {
				if ($this->navigation_bar->isVisible()) {
					$this->addTempVar('navigation_bar', $this->navigation_bar->getAsString());
				}
			}
		}

		$visible_cols = $this->getVisibleCols();

		if($this->_show_headers) {
			$headers = array();
			$i = 0;
			$is_sortable	= false;
			$order_field	= null;
			$order_mode		= null;

			if ($this->data->isSortable()) {
				$is_sortable = true;
				if ($this->data->hasOrder()) {
   					$order = $this->data->getOrder();
   					list($order_field, $order_mode)	= each($order);
				}
			}

			foreach($visible_cols as $col_name) {
				$col =& $this->cols->$col_name;
				if ($col->getType() == 'action') {
					$headers[$i]['value']  = '&nbsp;';
					$headers[$i]['order']  = '';
					$headers[$i]['action'] = '';
				} else {
					$headers[$i]['value'] = __($col->getLabel());
					$headers[$i]['order'] = null;

					if ($is_sortable and $col->isSortable()) {
						$headers[$i]['action'] = $col->composeStringActions(null, false);
					} else {
						$headers[$i]['action'] = "";
					}

					$data_field =& $this->data->fields->{$col->getName()};
					$field_name = $data_field->getName();
					$complete_field_name = $data_field->getSchemaTableField();
					if ($is_sortable and ($order_field == $field_name or $order_field == $complete_field_name)) {
						$headers[$i]['order'] = strtolower($order_mode);
					}
				}
				$i++;
			}
			$this->addTempVar('headers', $headers);
		}

		$table_cols = array();
		foreach ($visible_cols as $col_name) {
			$col =& $this->cols->$col_name;
			$a = array();
			$a['properties'] = $col->composeStringProperties();
			$table_cols[] = $a;
		}
		$this->addTempVar('table_cols', $table_cols);

		if ($this->data->getNumRows() > 0) {
			$this->addTempVar('table_rows', $this->rows->getRows($num_page, $rows));
		} else {
			$this->addTempVar('table_rows', null);
		}
		$return = $this->fetchTemplate();
		$this->clearTempVars();
		return $return;
	}

	/**
	 * @return P4A_Table
	 */
	public function showToolbar()
	{
		$this->toolbar->setVisible();
		return $this;
	}

	/**
	 * @return P4A_Table
	 */
	public function hideToolbar()
	{
		$this->toolbar->setVisible(false);
		return $this;
	}

	/**
	 * @return P4A_Table
	 */
	public function showNavigationBar()
	{
		$this->navigation_bar->setVisible();
		$this->_auto_navigation_bar = false;
		return $this;
	}

	/**
	 * @return P4A_Table
	 */
	public function hideNavigationBar()
	{
		$this->navigation_bar->setVisible(false);
		$this->_auto_navigation_bar = false;
		return $this;
	}

	/**
	 * @return P4A_Table
	 */
	public function showTitleBar()
	{
		if ($this->title_bar !== null) {
			$this->setTitle($this->getName());
		}
		$this->title_bar->setVisible();
		return $this;
	}

	/**
	 * Shows the bar with column names
	 * @return P4A_Table
	 */
	public function showHeaders()
	{
		$this->_show_headers = true;
		return $this;
	}

	/**
	 * Hides the bar with column names
	 * @return P4A_Table
	 */
	public function hideHeaders()
	{
		$this->_show_headers = false;
		return $this;
	}

	/**
	 * Return all column names
	 * @return array
	 */
	public function getCols()
	{
		return $this->cols->getNames();
	}

	/**
	 * Return an array with all names of visible columns
	 * @return array
	 */
	public function getVisibleCols()
	{
		$return = array();

		if (!empty($this->_cols_order)) {
			foreach ($this->_cols_order as $col) {
				if ($this->cols->$col->isVisible()) {
					$return[] = $col;
				}
			}
		} else {
			while ($col = $this->cols->nextItem()) {
				if ($col->isVisible()) {
					$return[] = $col->getName();
				}
			}
		}

		return $return;
	}

	/**
	 * Return an array with all names of invisible columns
	 * @return array
	 */
	public function getInvisibleCols()
	{
		$return = array();

		while ($col = $this->cols->nextItem()) {
			if (!$col->isVisible()) {
				$return[] = $col->getName();
			}
		}

		return $return;
	}

	/**
	 * Sets all passed columns visible.
	 * If no array is given, than sets all columns visible.
	 * @param array $cols
	 * @return P4A_Table
	 */
	public function setVisibleCols(array $cols = array())
	{
		$this->setInvisibleCols();
		if (sizeof($cols) == 0) {
			$cols = $this->getCols();
		}

		foreach ($cols as $col) {
			if (isset($this->cols->$col)) {
				$this->cols->$col->setVisible();
			} else {
				trigger_error("P4A_Table::setVisibleCol(): Unknow column $col", E_USER_ERROR);
			}
		}

		$this->_cols_order = $cols;
		return $this;
	}

	/**
	 * Sets all passed columns invisible.
	 * If no array is given, than sets all columns invisible.
	 * @param array $cols Columns names in indexed array
	 * @return P4A_Table
	 */
	public function setInvisibleCols(array $cols = array())
	{
		if (sizeof( $cols ) == 0) {
			$cols = $this->getCols();
		}

		foreach ($cols as $col) {
			if (isset($this->cols->$col)) {
				$this->cols->$col->setVisible(false);
			} else {
				trigger_error("P4A_Table::setInvisibleCols(): Unknow column $col", E_USER_ERROR);
			}
		}
		
		return $this;
	}

	/**
	 * Returns the current page number
	 * @return integer
	 */
	public function getCurrentPageNumber()
	{
		return $this->_current_page_number;
	}

	/**
	 * Sets the current page number
	 * @params integer $page
	 * @return P4A_Table
	 */
	public function setCurrentPageNumber($page)
	{
		$this->_current_page_number = $page;
		return $this;
	}

	/**
	 * Sets the page number reading it from the data source
	 * @return P4A_Table
	 */
	public function syncPageWithSource()
	{
		$this->setCurrentPageNumber($this->data->getNumPage());
		return $this;
	}
}

/**
 * Keeps the data for a single table column
 * @author Andrea Giardina <andrea.giardina@crealabs.it>
 * @author Fabrizio Balliano <fabrizio.balliano@crealabs.it>
 * @copyright CreaLabs SNC
 * @package p4a
 */
class P4A_Table_Col extends P4A_Widget
{
	/**
	 * Keeps the header string
	 * @var string
	 */
	protected $header = null;

	/**
	 * Data source for the field
	 * @var P4A_Data_Source
	 */
	public $data = null;

	/**
	 * The data source member that contains the values for this field
	 * @var string
	 */
	protected $data_value_field = null;

	/**
	 * The data source member that contains the descriptions for this field
	 * @var string
	 */
	protected $data_description_field	= null;

	/**
	 * Tells if the fields content is formatted or not
	 * @var boolean
	 */
	protected $formatted = true;

	/**
	 * Tells if the table can order by this col
	 * @var boolean
	 */
	protected $sortable = true;

	/**
	 * Type of the column
	 * @var string
	 */
	protected $_type = "text";

	/**
	 * @param string $name Mnemonic identifier for the object
	 */
	public function __construct($name)
	{
		parent::__construct($name);
		$this->setDefaultLabel();
		$this->addAjaxAction('onclick');
	}

	/**
	 * Sets the column visible (and add it as the last in the coloumn display order)
	 * @param boolean $visible
	 * @return P4A_Table_Col
	 */
	public function setVisible($visible = true)
	{
		parent::setVisible($visible);

		$p4a = P4A::singleton();
		$parent = $this->getParentID();
		$parent = $p4a->objects[$parent]->getParentID();
		$parent = $p4a->objects[$parent];

		if ($visible and !empty($parent->_cols_order) and !in_array($this->getName(), $parent->_cols_order)) {
			$parent->_cols_order[] = $this->getName();
		}
		
		return $this;
	}

	/**
	 * Sets the header for the column
	 * @param string $header
	 * @return P4A_Table_Col
	 * @deprecated 
	 */
	public function setHeader($header)
	{
		$this->setLabel($header);
		return $this;
	}

	/**
	 * Returns the header for the column.
	 * @access public
	 * @see $header
	 */
	public function getHeader()
	{
		return $this->getLabel();
	}

	/**
	 * If we use fields like combo box we have to set a data source.
	 * By default we'll take the data source primary key as value field
	 * and the first fiels (not pk) as description.
	 * @param P4A_Data_Source $data_source
	 * @return P4A_Table_Col
	 */
	public function setSource(P4A_Data_Source $data_source)
	{
		$this->data = $data_source;
		$pk = $this->data->getPk();

		if ($pk !== null) {
			if ($this->getSourceValueField() === null) {
				if (is_array($pk)) {
					trigger_error("P4A_Table::setSource(): Columns support only one primary key");
				} else {
					$this->setSourceValueField($pk);
				}
			}

			if ($this->getSourceDescriptionField() === null) {
				$source_value = $this->getSourceValueField();
				$names = $this->data->fields->getNames();
				foreach ($names as $name) {
					if ($name != $source_value) {
						$this->setSourceDescriptionField($name);
						break;
					}
				}
			}
		}
		
		return $this;
	}

	/**
	 * Sets what data source member is the keeper of the field's value
	 * @param string $name The name of the data source member
	 * @return P4A_Table_Col
	 */
	public function setSourceValueField($name)
	{
		$this->data_value_field = $name;
		return $this;
	}

	/**
	 * Sets what data source member is the keeper of the field's description
	 * @param string $name The name of the data source member
	 * @return P4A_Table_Col
	 */
	public function setSourceDescriptionField($name)
	{
		$this->data_description_field = $name;
		return $this;
	}

	/**
	 * Returns the name of the data source member that keeps the field's value.
	 * @return string
	 */
	public function getSourceValueField()
	{
		return $this->data_value_field;
	}

	/**
	 * Returns the name of the data source member that keeps the field's description
	 * @return string
	 */
	public function getSourceDescriptionField()
	{
		return $this->data_description_field;
	}

	/**
	 * Translate the value with the description
	 * @param string $value The value to translate
	 * @return string
	 */
	public function getDescription($value)
	{
		if (!$this->data) {
			return $value;
		} else {
			$row = $this->data->getPkRow($value);
			if (is_array($row)) {
				if (isset($row[$this->data_description_field])) {
					return $row[$this->data_description_field];
				} else {
					return null;
				}
			} else {
				return $value;
			}
		}
	}

	/**
	 * Sets/returns if the column should be formatted
	 *
	 * @param boolean $formatted
	 * @return boolean|P4A_Table_Col
	 */
	public function isFormatted($formatted = null)
	{
		if ($formatted === null) return $this->formatted;
		$this->formatted = $formatted;
		return $this;
	}

	/**
	 * Tell if the column is sortable or not
	 * @param boolean $sortable
	 * @return P4A_Table_Col
	 */
	public function isSortable($sortable = null)
	{
		if ($sortable === null) return $this->sortable;
		$this->sortable = $sortable;
		return $this;
	}

	/**
	 * Sets the type of the column (text|image|action)
	 * @param string $type
	 * @return P4A_Table_Col
	 */
	public function setType($type)
	{
		$this->_type = $type;
		return $this;
	}

	/**
	 * Gets the type of the column (text|image|action)
	 * @return string
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * @param array $aParams
	 * @return unknown
	 */
	public function onClick($aParams)
	{
		if ($this->isActionTriggered('beforeclick')) {
			if ($this->actionHandler('beforeclick', $aParams) == ABORT) return ABORT;
		}

		if ($this->getType() == 'action') {
			$p4a = P4A::singleton();
			$cols = $p4a->getObject($this->getParentID());
			$table = $p4a->getObject($cols->getParentID());
			if ($table->data->row($aParams[0] + (($table->getCurrentPageNumber() - 1) * $table->data->getPageLimit()) + 1) == ABORT) return ABORT;
		} else {
			$this->order();
		}
		
		if ($this->isActionTriggered('afterclick')) {
			if ($this->actionHandler('afterclick', $aParams) == ABORT) return ABORT;
		}
	}

	public function order()
	{
		$p4a = P4A::singleton();
		$parent = $p4a->getObject($this->getParentID());
		$parent = $p4a->getObject($parent->getParentID());
		$parent->redesign();

		if ($parent->data->isSortable()) {
			$data_field = $parent->data->fields->{$this->getName()};
			$field_name = $data_field->getName();

			$order_mode = P4A_ORDER_ASCENDING;
			if ($parent->data->hasOrder()) {
				list($order_field, $order_mode)	= each($parent->data->getOrder());
				if ($order_field == $field_name) {
   					if ($order_mode == P4A_ORDER_ASCENDING) {
   						$order_mode = P4A_ORDER_DESCENDING;
   					} else {
						$order_mode = P4A_ORDER_ASCENDING;
					}
				} else {
					$order_mode = P4A_ORDER_ASCENDING;
				}
			}
			$parent->data->setOrder($field_name, $order_mode);
			$parent->data->updateRowPosition();
		}
	}
}

/**
 * Keeps all the data for all the rows.
 * @author Andrea Giardina <andrea.giardina@crealabs.it>
 * @author Fabrizio Balliano <fabrizio.balliano@crealabs.it>
 * @copyright CreaLabs SNC
 * @package p4a
 */
class P4A_Table_Rows extends P4A_Widget
{
	/**
	 * Class constructor.
	 * By default we add an onClick action.
	 * @param string $name Mnemonic identifier for the object
	 */
	public function __construct($name = 'rows')
	{
		parent::__construct($name);
		$this->addAction('onclick');
	}

	/**
	 * Sets the max height for the data rows.
	 * This is done adding a scrollbar to the table body.
	 * @param integer $height
	 * @param string $unit (px|pt|em)
	 * @return P4A_Table_Rows
	 */
	public function setMaxHeight($height, $unit = 'px')
	{
		$this->setStyleProperty('max-height', $height . $unit);
		return $this;
	}

	/**
	 * Retrive data for the current page
	 * @return array
	 */
	public function getRows($num_page, $rows)
	{
		$p4a = P4A::singleton();

		$aReturn = array();
		$parent = $p4a->getObject($this->getParentID());
		$num_page_from_data_source = $parent->data->getNumPage();
		$aCols = $parent->getVisibleCols();
		$limit = $parent->data->getPageLimit();
		$offset = $parent->data->getOffset();
		$enabled = $this->isEnabled();
		$action = null;

		if ($this->isActionTriggered('beforedisplay')) {
			$rows = $this->actionHandler('beforedisplay', $rows);
		}

		$i = 0;
		foreach ($rows as $row_number=>$row) {
			$j = 0;
			if ($i%2 == 0) {
				$aReturn[$i]['row']['even'] = true;
			} else {
				$aReturn[$i]['row']['even'] = false;
			}

			if (($num_page == $num_page_from_data_source) and ($row_number + $offset + 1 == $parent->data->getRowNumber())) {
				$aReturn[$i]['row']['active'] = true;
			} else {
				$aReturn[$i]['row']['active'] = false;
			}

			if (isset($row['_p4a_enabled'])) {
				$row_enabled = $row['_p4a_enabled'];
			} else {
				$row_enabled = true;
			}

			foreach($aCols as $col_name) {
				$col_enabled = $parent->cols->$col_name->isEnabled();
				$aReturn[$i]['cells'][$j]['action'] = ($enabled and $row_enabled and $col_enabled) ? $this->composeStringActions(array($row_number, $col_name)) : '';
				$aReturn[$i]['cells'][$j]['clickable'] = ($enabled and $row_enabled and $col_enabled) ? 'clickable' : '';

				if ($parent->cols->$col_name->data) {
					$aReturn[$i]['cells'][$j]['value'] = $parent->cols->$col_name->getDescription($row[$col_name]);
					$aReturn[$i]['cells'][$j]['type'] = $parent->data->fields->$col_name->getType();
				} elseif ($parent->cols->$col_name->getType() == "image") {
					$value = $row[$col_name];
					if (!empty($value)) {
						$value = substr($value, 1, -1);
						$value = explode(',', $value);
						list($type) = explode('/',$value[3]);
						if ($type == 'image') {  
							$image_src = $value[1];
							$thumb_height = P4A_TABLE_THUMB_HEIGHT;
							if (P4A_GD) {
								$image_src = '.?_p4a_image_thumbnail=' . urlencode("$image_src&$thumb_height");
								$aReturn[$i]['cells'][$j]['value'] = "<img src='$image_src' alt='' />";
							} else {
								$image_src = P4A_UPLOADS_PATH . $image_src;
								$aReturn[$i]['cells'][$j]['value'] = "<img src='$image_src' height='$thumb_height' alt='' />";
							}
						} else {
							$aReturn[$i]['cells'][$j]['value'] = $value[0];
						}
					} else {
						$aReturn[$i]['cells'][$j]['value'] = '';													
					}
					$aReturn[$i]['cells'][$j]['type'] = $parent->data->fields->$col_name->getType();
				} elseif ($parent->cols->$col_name->getType() == "action") {
					$aReturn[$i]['cells'][$j]['value'] = __($parent->cols->$col_name->getLabel());
					$aReturn[$i]['cells'][$j]['type'] = 'action';
					$aReturn[$i]['cells'][$j]['action'] = $enabled ? $parent->cols->$col_name->composeStringActions(array($row_number, $col_name)) : '';
				} else {
					if ($parent->cols->$col_name->isFormatted()) {
						if ($parent->cols->$col_name->isActionTriggered('onformat')) {
							$aReturn[$i]['cells'][$j]['value'] = $parent->cols->$col_name->actionHandler('onformat', $row[$col_name], $parent->data->fields->$col_name->getType(), $parent->data->fields->$col_name->getNumOfDecimals());
						} else {
							$aReturn[$i]['cells'][$j]['value'] = $p4a->i18n->format($row[$col_name], $parent->data->fields->$col_name->getType(), $parent->data->fields->$col_name->getNumOfDecimals(), false);
						}
					} else {
						$aReturn[$i]['cells'][$j]['value'] = $row[$col_name];
					}
					$aReturn[$i]['cells'][$j]['type'] = $parent->data->fields->$col_name->getType();
				}

				$j++;
			}
			$i++;
		}

		return $aReturn;
	}

	/**
	 * onClick action handler for the row.
	 * We move pointer to the clicked row.
	 * @param array $aParams
	 */
	public function onClick($aParams)
	{
		if ($this->actionHandler('beforeclick', $aParams) == ABORT) return ABORT;

		$parent = P4A::singleton()->getObject($this->getParentID());
		if ($parent->data->row($aParams[0] + (($parent->getCurrentPageNumber() - 1) * $parent->data->getPageLimit()) + 1) == ABORT) return ABORT;

		if ($this->actionHandler('afterclick', $aParams) == ABORT) return ABORT;
	}
}

/**
 * The gui widgets to navigate the table.
 * @author Andrea Giardina <andrea.giardina@crealabs.it>
 * @author Fabrizio Balliano <fabrizio.balliano@crealabs.it>
 * @package p4a
 */
class P4A_Table_Navigation_Bar extends P4A_Frame
{
	/**
	 * @var P4A_Collection
	 */
	public $buttons = null;

	public function __construct()
	{
		parent::__construct("table_navigation_bar");
		$this->build("p4a_collection", "buttons");

		$this->addButton('go', 'apply', 'right');
		$this->buttons->go->setLabel("Go");
		$this->buttons->go->implement('onclick', $this, 'goOnClick');

		$this->buttons->build(P4A_FIELD_CLASS, 'page_number');
		$this->buttons->page_number->label->setStyleProperty("text-align", "right");
		$this->buttons->page_number->label->setWidth(80);
		$this->buttons->page_number->setWidth(30);
		$this->buttons->page_number->addAjaxAction('onreturnpress');
		$this->buttons->page_number->implement('onreturnpress', $this, 'goOnClick');
		$this->anchorRight($this->buttons->page_number);

		$this->buttons->build('p4a_label', 'current_page');
		$this->anchorLeft($this->buttons->current_page);

		if (P4A::singleton()->isHandheld()) {
			$this->addButton('first');
			$this->buttons->first->setLabel('<<');
			$this->addButton('prev');
			$this->buttons->prev->setLabel('<');
			$this->addButton('next');
			$this->buttons->next->setLabel('>');
			$this->addButton('last');
			$this->buttons->last->setLabel('>>');
			$this->buttons->go->setVisible(false);
			$this->buttons->page_number->setVisible(false);
		} else {
			$this->addButton('last', 'last', 'right');
			$this->buttons->last->setLabel("Go to the last page");
			$this->addButton('next', 'next', 'right');
			$this->buttons->next->setLabel("Go to the next page");
			$this->addButton('prev', 'prev', 'right');
			$this->buttons->prev->setLabel("Go to the previous page");
			$this->addButton('first', 'first', 'right');
			$this->buttons->first->setLabel("Go to the first page");
		}
		
		$this->buttons->last->implement('onclick', $this, 'lastOnClick');
		$this->buttons->next->implement('onclick', $this, 'nextOnClick');
		$this->buttons->prev->implement('onclick', $this, 'prevOnClick');
		$this->buttons->first->implement('onclick', $this, 'firstOnClick');
	}

	/**
	 * @param string $button_name
	 * @param string $icon
	 * @param string $float
	 * @return P4A_Button
	 */
	public function addButton($button_name, $icon = null, $float = "left")
	{
		$button = $this->buttons->build("p4a_button", $button_name);
		$button->addAjaxAction('onclick');

		if (strlen($icon)>0) {
			$button->setIcon($icon);
			$button->setSize(16);
		}

		$anchor = "anchor" . $float;
		$this->$anchor($button, "2px");
		return $button;
	}

	/**
	 * @return string
	 */
	public function getAsString()
	{
		$parent = P4A::singleton()->getObject($this->getParentID());

		$this->buttons->page_number->setLabel('Go to page');
		$this->buttons->page_number->setNewValue($parent->getCurrentPageNumber());

		$num_pages = $parent->data->getNumPages();
		if ($num_pages == 0) {
			$num_pages = 1;
		}

		$current_page  = __('Page');
		$current_page .= ' ';
		$current_page .= $parent->getCurrentPageNumber();
		$current_page .= ' ';
		$current_page .= __('of');
		$current_page .= ' ';
		$current_page .= $num_pages;
		$current_page .= ' ';
		$this->buttons->current_page->setLabel($current_page);
		return parent::getAsString();
	}

	/**
	 * Action handler for "next" button click
	 */
	public function nextOnClick()
	{
		$parent = P4A::singleton()->getObject($this->getParentID());

		$num_page = $parent->getCurrentPageNumber() + 1;
		$num_pages = $parent->data->getNumPages();

		if ($num_page > $num_pages) {
			$num_page -= 1;
		}

		$parent->setCurrentPageNumber($num_page);
		$parent->redesign();
	}

	/**
	 * Action handler for "previous" button click
	 */
	public function prevOnClick()
	{
		$parent = P4A::singleton()->getObject($this->getParentID());

		$num_page = $parent->getCurrentPageNumber() - 1;
		if ($num_page < 1) {
			$num_page = 1;
		}

		$parent->setCurrentPageNumber($num_page);
		$parent->redesign();
	}

	/**
	 * Action handler for "first" button click
	 */
	public function firstOnClick()
	{
		$parent = P4A::singleton()->getObject($this->getParentID());
		$parent->setCurrentPageNumber(1);
		$parent->redesign();
	}

	/**
	 * Action handler for "last" button click
	 */
	public function lastOnClick()
	{
		$parent = P4A::singleton()->getObject($this->getParentID());
		$parent->setCurrentPageNumber($parent->data->getNumPages());
		$parent->redesign();
	}

	/**
	 * Action handler for "go" button click
	 */
	public function goOnClick()
	{
		$parent = P4A::singleton()->getObject($this->getParentID());

		$num_page = (int)$this->buttons->page_number->getNewValue();
		$num_pages = $parent->data->getNumPages();

		if ($num_page < 1) {
			$num_page = 1;
		} elseif ($num_page > $num_pages) {
			$num_page = $num_pages;
		}

		$parent->setCurrentPageNumber($num_page);
		$parent->redesign();
	}

}