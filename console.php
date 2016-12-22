<?php

// This is the configuration for yiic console application.
// Any writable CConsoleApplication properties can be configured here.
return array(
	'basePath'=>dirname(__FILE__),
	'name'=>'My Console Application',

	// preloading 'log' component
	'preload'=>array('log'),

    'import' => [
        'application.models.*',
        'application.components.*',
    ],

	// application components
	'components' => [
        'db' =>  [
            'connectionString' => 'mysql:host=127.0.0.1;dbname=fund_statistics',
            'emulatePrepare' => true,
            'username' => 'root',
            'password' => '19880217',
            'charset' => 'utf8',
        ],
		// uncomment the following to use a MySQL database
		/*
		'db'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=testdrive',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => '',
			'charset' => 'utf8',
		),
		*/
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning',
				),
			),
		),
	],
);