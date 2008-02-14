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
 * @author Andrea Giardina <andrea.giardina@crealabs.it>
 * @author Fabrizio Balliano <fabrizio.balliano@crealabs.it>
 * @copyright CreaLabs SNC
 * @package p4a
 */
class P4A_Collection extends P4A_Object
{
	protected $_pointer = 0;

	//todo: in caso di destroy di un elemento fare l'unset della chiave e ridurre l'array
	public function nextItem()
	{
		$p4a = P4A::singleton();
		if ($this->_pointer < $this->getNumItems()) {
			$id = $this->_objects[$this->_pointer];
			$this->_pointer++;
			if (!isset($p4a->objects[$id]) or !is_object($p4a->objects[$id])) {
				$this->_pointer--;
				unset($this->_objects[$this->_pointer]);
				$this->_objects = array_values($this->_objects);
				return $this->nextItem();
			} else {
				return $p4a->objects[$id];
			}
		} else {
			$this->_pointer = 0;
			$ret = null; //php 4.4 fix
			return $ret;
		}
	}

	/**
	 * @return integer
	 */
	public function getNumItems()
	{
		return count($this->_objects);
	}

	/**
	 * @return P4A_Collection
	 */
	public function reset()
	{
		$this->_pointer = 0;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getNames()
	{
		$names = array();

		while ($item = $this->nextItem()) {
			$names[] = $item->getName();
		}

		return $names;
	}
}