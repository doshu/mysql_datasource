<?php

	
	class MysqlAdapter extends PDO {
		
		private $__dsn = 'mysql:dbname=%s;host=%s';
		private $__defaultSelectOptions = array(
			'fields' => array('*'),
			'table' => array()
		);
		private $__defaultInsertOptions = array(
			'table' => '',
			'values' => array()
		);
		private $__defaultDeleteOptions = array(
			'table' => ''
		);
		private $__defaultUpdateOptions = array(
			'table' => '',
			'set' => array()
		);
		private $__conditionsOperators = array(
			'AND', 
			'OR',
			'NOT'
		);
		
		private $__toPrepareFields = array();
		private $__toPrepareFieldsIndex = 0;
		
		public function __construct($host, $db, $user, $pwd) {
			parent::__construct(sprintf($this->__dsn, $db, $host), $user, $pwd);
			$this->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
		}
		
		
		public function query($query, $param) {
			$pdoStatement = $this->prepare($query);
			$pdoStatement->execute($param);
			if($this->queryIsSelect($query)) {
				$result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
			}
			else {
				$result = $pdoStatement->rowCount();
			}
			$pdoStatement->closeCursor();
		}
		
		
		public function queryIsSelect($q) {
			if(preg_match('/^select/i', $q))
				return true;
			return false;
		}
		
		/*
		 * performe delete statement
		 * $opt = array('table' => ..., 'join' => ..., 'where' => ...)
		 * 
		 */
		public function delete($opt) {
			
			$statement = $this->buildDelete($opt);
			$pdoStatement = $this->prepare($statement);
			$this->__bindValues($pdoStatement);
			
			$pdoStatement->execute();
			$pdoStatement->closeCursor();
			$this->__reset();
		}
		
		
		public function buildDelete($opt) {
		
			$opt = $this->__extendDefaultOptions($opt, $this->__defaultDeleteOptions);
			
			$statement = 'DELETE FROM %s';
			$statement = sprintf(
				$statement, 
				$this->__prepareFields($opt['table'])
			);
			
			if(isset($opt['join'])) {
				$joinsString = $this->__parseJoin($opt['join']);
				if(!empty($joinsString))
					$statement .= ' '.$joinsString;
			}
			
			if(isset($opt['where'])) {
				$whereString = $this->__parseConditions($opt['where']);
				if(!empty($whereString))
					$statement .= ' WHERE '.$whereString;
			}
			
			if(isset($opt['limit'])) {
				$limitString = $this->__parseLimit($opt['limit']);
				if(!empty($limitString))
					$statement .= ' LIMIT '.$limitString;
			}			
			
			return $statement;
		}
		
		
		/*
		 * performe update statement
		 * $opt = array('table' => ..., 'join' => ..., 'set' => ... 'where' => ...)
		 * 
		 */
		public function update($opt) {
			
			$statement = $this->buildUpdate($opt);
			$pdoStatement = $this->prepare($statement);
			$this->__bindValues($pdoStatement);
			
			$pdoStatement->execute();
			$pdoStatement->closeCursor();
			$this->__reset();
		}
		
		
		public function buildUpdate($opt) {
		
			$opt = $this->__extendDefaultOptions($opt, $this->__defaultUpdateOptions);
			
			$statement = 'UPDATE %s';
			$statement = sprintf(
				$statement, 
				$this->__prepareFields($opt['table'])
			);
			
			if(isset($opt['join'])) {
				$joinsString = $this->__parseJoin($opt['join']);
				if(!empty($joinsString))
					$statement .= ' '.$joinsString;
			}
			
			$statement .= ' SET '.$this->__parseSet($opt['set']);
			
			if(isset($opt['where'])) {
				$whereString = $this->__parseConditions($opt['where']);
				if(!empty($whereString))
					$statement .= ' WHERE '.$whereString;
			}			
			
			return $statement;
		}
		
		
		private function __parseSet($set) {
			$parsedSet = array();
			foreach($set as $field => $val) {
				if(is_numeric($field)) {
					$parsedSet[] = $val;
				}
				else {
					$_p = $this->__getNextToPrepare();
					$parsedSet[] = $field.' = '.$_p;
					$this->__pushToPrepareFields(array($_p => $val));	
				}
			}
			return implode(', ', $parsedSet);
		}
		
		
		/*
		 * performe insert statement
		 * $opt = array('table' => ..., 'values' => ..., 'select' => ...)
		 * 
		 */
		public function insert($opt) {
			
			$statement = $this->buildInsert($opt);
			$pdoStatement = $this->prepare($statement);
			$this->__bindValues($pdoStatement);
			
			//$pdoStatement->execute();
			$pdoStatement->closeCursor();
			$this->__reset();
		}
		
		
		public function buildInsert($opt) {
			
			$opt = $this->__extendDefaultOptions($opt, $this->__defaultInsertOptions);
			$statement = 'INSERT INTO %s';
			$statement = sprintf(
				$statement, 
				$this->__prepareFields($opt['table'])
			);
			
			if(isset($opt['select'])) {
				$valuesString = $this->__prepareInsertSelect($opt['select']);
			}
			else {
				$valuesString = $this->__prepareInsertValues($opt['values']);
			}
			
			if(!empty($valuesString))
					$statement .= ' '.$valuesString;
			
			return $statement;
		}
		
		
		private function __prepareInsertSelect($opt) {
			
			$opt = $this->__extendDefaultOptions($opt, $this->__defaultSelectOptions);
			$insertSelect = '(%s) %s';
			return sprintf(
				$insertSelect,
				implode(', ', $opt['fields']),
				$this->buildSelect($opt)
			);
		}
		
		
		private function __prepareInsertValues($values) {
			
			$insertSelect = '(%s) VALUES (%s)';
			$fields = array();
			$toPrepare = array();
			
			foreach($values as $field => $value) {
				$fields[] = $field;
				$_p = $this->__getNextToPrepare();
				$toPrepare[] = $_p;
				$this->__pushToPrepareFields(array($_p => $value));
			}
			
			return sprintf(
				$insertSelect,
				implode(', ', $fields),
				implode(', ', $toPrepare)
			);
		}
		
		
		/*
		 * performe select statement
		 * $opt = array('fields' => ..., 'table' => ..., 'join' => ..., 'where' => ..., 'group' => ..., 'having' => ..., 'order' => ...)
		 * 
		 * 'join' => array('type' => ..., 'table' => ..., 'on' => conditions array)
		 *
		 * @param array $opt 
		 * @return array $result
		 *
		 */
		public function select($opt) {
		
			$statement = $this->buildSelect($opt);
			$pdoStatement = $this->prepare($statement);
			$this->__bindValues($pdoStatement);
			
			$pdoStatement->execute();
			$result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
			$pdoStatement->closeCursor();
			$this->__reset();
			return $result;
		}
		
		
		public function buildSelect($opt) {
		
			$opt = $this->__extendDefaultOptions($opt, $this->__defaultSelectOptions);
			
			$statement = 'SELECT %s FROM %s';
			$statement = sprintf(
				$statement, 
				$this->__prepareFields($opt['fields']),
				$this->__prepareFields($opt['table'])
			);
			
			if(isset($opt['join'])) {
				$joinsString = $this->__parseJoin($opt['join']);
				if(!empty($joinsString))
					$statement .= ' '.$joinsString;
			}
			
			if(isset($opt['where'])) {
				$whereString = $this->__parseConditions($opt['where']);
				if(!empty($whereString))
					$statement .= ' WHERE '.$whereString;
			}
			
			if(isset($opt['group'])) {
				$groupString = $this->__prepareFields($opt['group']);
				if(!empty($groupString))
					$statement .= ' GROUP BY '.$groupString;
			}
			
			if(isset($opt['having'])) {
				$havingString = $this->__parseConditions($opt['having']);
				if(!empty($havingString))
					$statement .= ' HAVING '.$havingString;
			}
			
			if(isset($opt['order'])) {
				$orderString = $this->__parseOrders($opt['order']);
				if(!empty($orderString))
					$statement .= ' ORDER BY '.$orderString;
			}
			
			if(isset($opt['limit'])) {
				$limitString = $this->__parseLimit($opt['limit']);
				if(!empty($limitString))
					$statement .= ' LIMIT '.$limitString;
			}
			
			return $statement;
		}
		
		
		private function __bindValues($statement) {
			
			foreach($this->__toPrepareFields as $name => $field) {
				if(is_numeric($field)) {
					$statement->bindValue($name, $field, PDO::PARAM_INT);
				}
				elseif(is_bool($field)) {
					$statement->bindValue($name, $field, PDO::PARAM_BOOL);
				}
				elseif(is_null($field)) {
					$statement->bindValue($name, $field, PDO::PARAM_NULL);
				}
				else {
					$statement->bindValue($name, $field, PDO::PARAM_STR);
				}
			}
		}
		
		private function __parseOrders($orders) {
			$parsedOrders = array();
			foreach($orders as $field => $dir) {
				if(is_numeric($field)) {
					$field = $dir;
					$dir = 'DESC';
				}
				$parsedOrders[] = $field.' '.$dir;
			}
			return implode(', ', $parsedOrders);
		}
		
		
		private function __parseLimit($limit) {
			$limit = (array)$limit;
			return implode(', ', $limit);
		}
		
		
		private function __parseConditions($conditions) {
			return $this->__buildConditionsQuery($conditions, 'AND');
		}
		
		private function __buildConditionsQuery($conditions, $op, $not = false) {
			$conditionsQuery = array();
			foreach($conditions as $key => $val) {
				$key = trim($key);
				if(in_array($key, $this->__conditionsOperators)) {
					if($key == 'NOT') {
						$conditionsQuery[] = $this->__buildConditionsQuery($val, 'AND', true);
					}
					else {
						$conditionsQuery[] = $this->__buildConditionsQuery($val, $key);
					}
				}
				else {
					
					if(is_numeric($key) && is_string($val)) {
						$conditionsQuery[] = $val;
					}
					else {
						if(is_numeric($key) && is_array($val)) {
							list($key, $val) = each($val);
						}
						list($parsedConditionFlield, $val) = $this->__parseConditionField($key, $val);
						$this->__pushToPrepareFields($val);
						$conditionsQuery[] = $parsedConditionFlield;
					}
				}
			}
			$ret = ' ('.implode(' '.$op.' ', $conditionsQuery).')';
			if($not)
				$ret = 'NOT'.$ret;
			return $ret;
		}
		
		
		private function __pushToPrepareFields($fields) {
			foreach($fields as $name => $val)  {
				$this->__toPrepareFields[$name] = $val;
			}
		}
		
		private function __reset() {
			$this->__toPrepareFields = array();
			$this->__toPrepareFieldsIndex = 0;
		}
		
		private function __getNextToPrepare() {
			return ':p'.$this->__toPrepareFieldsIndex++;
		}
		
		private function __parseConditionField($name, $value) {
		
			$_p = $this->__getNextToPrepare();
		
			if(strpos($name, ' >=')) {
				return array($this->__extractFromString(' >=', $name).' >= '.$_p, array($_p => $value));
			}
			
			elseif(strpos($name, ' <=')) {
				return array($this->__extractFromString(' <=', $name).' <= '.$_p, array($_p => $value));
			}
			
			elseif(strpos($name, ' <>')) {
				return array($this->__extractFromString(' <>', $name).' <> '.$_p, array($_p => $value));
			}
			
			elseif(strpos($name, ' >')) {
				return array($this->__extractFromString(' >', $name).' > '.$_p, array($_p => $value));
			}
			
			elseif(strpos($name, ' <')) {
				return array($this->__extractFromString(' <', $name).' < '.$_p, array($_p => $value));
			}
			
			elseif(strpos($name, ' =')) {
				return array($this->__extractFromString(' =', $name).' = '.$_p, array($_p => $value));
			}
			
			elseif(strpos($name, ' BETWEEN')) {
				$name = $this->__extractFromString(' BETWEEN', $name);
				if(is_array($value)) {
					$_p2 = $this->__getNextToPrepare();
					return array($name.' BETWEEN '.$_p.' AND '.$_p2, array($_p => $value[0], $_p2 => $value[1]));
				}
				return array($name.' BETWEEN '.$_p, array($_p => $value));
			}
			
			elseif(strpos($name, ' IS NOT')) {
				return array($this->__extractFromString(' IS NOT', $name).' IS NOT '.$_p, array($_p => $value));
			}
			
			elseif(strpos($name, ' IS')) {
				return array($this->__extractFromString(' IS', $name).' IS  '.$_p, array($_p => $value));
			}
			
			elseif(strpos($name, ' IN')) {
				$value = (array)$value;
				$in = array();
				for($i = 0, $j = count($value); $i < $j; $i++) {
					$in[] = $this->__getNextToPrepare();
				}
				$returnValue = array_combine($in, $value);
				$in = '('.implode(', ', $in).')';
				return array($this->__extractFromString(' IN', $name).' IN '.$in, $returnValue);
			}
			
			else {
				if($value === null) {
					return array($name.' IS  '.$_p, array($_p => null));
				}
				elseif(is_array($value)) {
					$value = (array)$value;
					$in = array();
					for($i = 0, $j = count($value); $i < $j; $i++) {
						$in[] = $this->__getNextToPrepare();
					}
					$returnValue = array_combine($in, $value);
					$in = '('.implode(', ', $in).')';
					return array($name.' IN '.$in, $returnValue);
				}
				return array($name.' = '.$_p, array($_p => $value));
			}
		}
		
		private function __extractFromString($toExtract, $string) {
			return trim(str_replace($toExtract, '', $string));
		}
		
		private function __parseJoin($joins) {
			$parsedJoins = array();
			foreach($joins as $join) {
				if(is_array($join) && isset($join['table'])) {
					$joinString = "";
					$joinType = isset($join['type'])?$join['type']:'LEFT';
					$joinString .= $joinType.' JOIN '.$this->__prepareFields($join['table']);
					
					if(isset($join['on'])) {
						$joinConditions = $this->__parseConditions($join['on']);
						if(!empty($joinConditions))
							$joinString .= ' ON '.$joinConditions;
					}
					
					$parsedJoin[] = $joinString;
				}
			}
			
			return implode(' ', $parsedJoin);
		}
		
		private function __prepareFields($fields) {
			$fields = (array)$fields;
			$parsedFields = array();
			foreach($fields as $field => $alias) {
				if(is_numeric($field)) {
					$parsedFields[] = $alias;
				}
				else {
					$parsedFields[] = $field.' AS '.$alias;
				}
			}
			return implode(', ', $parsedFields);
		}
		
		private function __extendDefaultOptions($opt, $ext) {
			return array_merge($ext, $opt);
		}
		
		public function getCountField() {
			return 'COUNT(*)';
		}
		
		public function getLastInsertId($seq) {
			return $this->lastInsertId();
		}
	}

?>
