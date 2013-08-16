<?php

	/*
	require_once "Datasource/mysql.php";
	
	$c = new MysqlAdapter('127.0.0.1', 'mes', 'root', 'shodan');
	*/
	/*
	$c->select(array(
		'fields' => array('*', 'SUM(age)'),
		'table' => 'members',
		'join' => array(
			array(
				'table' => array('recipients' => 'r'),
				'type' => 'LEFT',
				'on' => array(
					'state >' => 'italia',
					'OR' => array(
						'id <=' => 10,
						'name <>' => 'ciao',
						'AND' => array(
							array('surname >=' => 'miao'),
							array('surname' => null),
							'address BETWEEN' => array(2, 10)
						),
						'NOT' => array(
							'city IN' => array('biella', 'vigliano')
						)
					)
				)
			)
		),
		'where' => array(
			'id <=' => 10,
			'name <>' => 'ciao',
			'AND' => array(
				array('surname >=' => 'miao'),
				array('surname' => null),
				'address BETWEEN' => array(2, 10)
			),
			'NOT' => array(
				'city' => 'biella'
			)
		),
		'group' => array(
			'name',
			'surname'
		),
		'having' => array(
			'SUM(age) BETWEEN' => array(18, 35)
		),
		'order' => array(
			'name',
			'surname' => 'ASC'
		)
	));
	*/
	
	/*
	$res = $c->select(array(
		'fields' => '*',
		'table' => 'members',
		'where' => array(
			'AND' => array(
				array('id >' => 10),
				array('id <' => 1000)
			)
		),
		'order' => array('id')	
	));
	
	print_r($res);
	*/
	/*
	$res = $c->select(array(
		'fields' => '*',
		'table' => 'members',
		'where' => array(
			'id <>' => 10, 
			'id IN ('.$c->buildSelect(array('fields' => 'id', 'table' => 'members', 'where' => array('id >' => 10, 'id <' => 1000))).')'
		),
		'order' => array('id')	
	));
	*/
	
	/*
	$c->insert(array(
		'table' => 'members',
		'values' => array(
			'email' => 'gnomo@cici.it',
			'user_id' => 2
		)
	));
	*/
	
	/*
	$c->insert(array(
		'table' => 'members',
		'select' => array(
			'table' => 'members',
			'fields' => array('email', 'id', 'user_id'),
			'where' => array('user_id' => 1)
		)
	));
	*/
	
	/*
	$c->update(array(
		'table' => 'members',
		'join' => array(
			array(
				'table' => array('recipients' => 'r'),
				'type' => 'LEFT',
				'on' => array(
					'state >' => 'italia',
					'OR' => array(
						'id <=' => 10,
						'name <>' => 'ciao',
						'AND' => array(
							array('surname >=' => 'miao'),
							array('surname' => null),
							'address BETWEEN' => array(2, 10)
						),
						'NOT' => array(
							'city IN' => array('biella', 'vigliano')
						)
					)
				)
			)
		),
		'set' => array(
			'name' => 'asd',
			't1.name = t2.name'
		),
		'where' => array(
			'AND' => array(
				array('id >' => 10),
				array('id <' => 1000)
			)
		)
	));
	*/
	
	
	require_once "Model.php";
	
	class Sending extends Model {
		public $table = 'sendings';
	}
	
	$s = new Sending();
	
	$s->load(7);
	
	print_r($s->data);
	
	
?>
