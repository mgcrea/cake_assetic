<?php
/**
 * Copyright 2012, Olivier Louvignes (http://olouv.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2011, Olivier Louvignes (http://olouv.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppHelper', 'View/Helper');

require_once(APP . 'Plugin' . DS . 'Assetic' . DS . 'Util' . DS . 'Process' . '.php');
require_once(APP . 'Plugin' . DS . 'Assetic' . DS . 'Util' . DS . 'ProcessBuilder' . '.php');
App::import('Vendor', 'Assetic/Util/PathUtils');

App::import('Vendor', 'Assetic/AssetWriter');
use Assetic\AssetWriter;
App::import('Vendor', 'Assetic/AssetManager');
use Assetic\AssetManager;

App::import('Vendor', 'Assetic/FilterManager');
use Assetic\FilterManager;
App::import('Vendor', 'Assetic/Asset/AssetInterface');
App::import('Vendor', 'Assetic/Asset/AssetCollectionInterface');
App::import('Vendor', 'Assetic/Filter/FilterCollection');
App::import('Vendor', 'Assetic/Exception/Exception');
App::import('Vendor', 'Assetic/Exception/FilterException');
App::import('Vendor', 'Assetic/Asset/Iterator/AssetCollectionFilterIterator');
App::import('Vendor', 'Assetic/Asset/Iterator/AssetCollectionIterator');
App::import('Vendor', 'Assetic/Asset/AssetCollection');
use Assetic\Asset\AssetCollection;
App::import('Vendor', 'Assetic/Asset/BaseAsset');
App::import('Vendor', 'Assetic/Asset/FileAsset');
use Assetic\Asset\FileAsset;

App::import('Vendor', 'Assetic/Asset/GlobAsset');
use Assetic\Asset\GlobAsset;

App::import('Vendor', 'Assetic/Factory/AssetFactory');
use Assetic\Factory\AssetFactory;

/**
 * A template helper that assists in generating CSS/JS static content
 */
class AsseticHelper extends AppHelper {

	public $helpers = array('Html');

	public static $config = array();
	public static $filterManager;

	protected $scriptAssetCollection;
	protected $scriptAssetManager;
	protected $scriptAssetWriter;

	protected $styleAssetCollection;
	protected $styleAssetManager;
	protected $styleAssetWriter;

	/**
	 * Configures this helper
	 */
	public static function config($config = array()) {

		$defaults = array(
			'optimize' => (Configure::read('debug') == 0),
			'debug' => (Configure::read('debug') > 0),
			'stylesPath' => CSS,
			'scriptsPath' => JS,
			'filters' => array()
		);
		$config += $defaults;

		// Merge config
		static::$config = array_merge(static::$config, $config);

		// Configure filters
		static::registerFilters($config['filters']);

	}

/**
 * Constructor
 *
 * @param View $View The View this helper is being attached to.
 * @param array $settings Configuration settings for the helper.
 */
	public function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings);

		// Force helper static configuration
		if(!static::$config) static::config();



		$this->styleAssetCollection = new AssetCollection();
		$this->styleAssetManager = new AssetManager();
		// Initialize static assets writer
		$this->styleAssetFactory = new AssetFactory(static::$config['stylesPath']);
		$this->styleAssetFactory->setAssetManager($this->styleAssetManager);
		$this->styleAssetFactory->setFilterManager(static::$filterManager);
		$this->styleAssetWriter = new AssetWriter(static::$config['stylesPath']);

		$this->scriptAssetCollection = new AssetCollection();
		$this->scriptAssetManager = new AssetManager();
		// Initialize static assets writer
		$this->scriptAssetWriter = new AssetWriter(static::$config['scriptsPath']);
	}

	public function css($source, $options = array()) {
		return $this->style($source, $options);
	}

	public function style($source, $options = array()) {

		$defaults = array(
			'type' => "style",
			'target' => false,
			'factory' => false,
			'filters' => array()
		);
		$options += $defaults;

		// Ensure arrays
		if(!is_array($source)) $source = array($source);
		if(!is_array($options['filters'])) $options['filters'] = array($options['filters']);

		$ac =& $this->styleAssetCollection;
		$aw =& $this->styleAssetWriter;

		// Resolve filters
		$filters = $this->resolveFilters($options['filters']);

		if($options['factory']) {

			foreach($source as &$leaf) {
				$leaf = $leaf .= '.less';//self::normalizeExtension($leaf, $options['type']);
			} unset($leaf);
			$asset = $this->styleAssetFactory->createAsset($source, $options['filters']);
			//$ac->add($asset);
			$asset->setTargetPath($options['target'] ?: self::normalizeExtension('combined', $options['type']));
			$aw->writeAsset($asset);
			echo $this->Html->css('combined') . "\n\t";

			return null;
		}

		foreach($source as $leaf) {
			$leaf = self::guessExtension($leaf, $options);

			if(strpos($leaf, '*')) $asset = new GlobAsset(CSS . $leaf, $filters);
			else $asset = new FileAsset(CSS . $leaf, $filters);
			$ac->add($asset);

			if(!static::$config['optimize']) {
				if((!empty($filters) || get_class($asset) == 'Assetic\Asset\GlobAsset')) {
					$leaf = $options['target'] ?: self::normalizeExtension($leaf, $options['type']);
					$asset->setTargetPath($leaf);
					$aw->writeAsset($asset);
				}
				echo $this->Html->css($leaf) . "\n\t";
			}

		}

		return null;

	}

	public function styles($options = array()) {

		if(!static::$config['optimize']) {
			return null;
		}

		$defaults = array(
			'target' => "main",
			'type' => "style",
			'force' => false,
			'version' => false,
			'filters' => array()
		);
		$options += $defaults;

		// Ensure arrays
		if(!is_array($options['filters'])) $options['filters'] = array($options['filters']);

		$ac =& $this->styleAssetCollection;
		$am =& $this->styleAssetManager;
		$aw =& $this->styleAssetWriter;

		// Resolve filters
		$filters = $this->resolveFilters($options['filters']);
		foreach($filters as $filter) {
			$ac->ensureFilter($filter);
		}

		$options['target'] = self::normalizeExtension($options['target'], $options['type']);
		$ac->setTargetPath($options['target']);
		$am->set(str_replace('.', '', $options['target']), $ac);

		//echo $ac->dump(); exit;
		// Write static assets
		if($options['force'] || !is_file(static::$config['stylesPath'] . DS . $options['target'])) {
			$aw->writeManagerAssets($am);
		}

		return $this->Html->css($options['target'] . ($options['version'] ? '?' . $options['version'] : ''), null, array('inline' => true));

	}

	public function script($source, $options = array()) {

		$defaults = array(
			'type' => "script",
			'target' => false,
			'filters' => array()
		);
		$options += $defaults;

		// Ensure arrays
		if(!is_array($source)) $source = array($source);
		if(!is_array($options['filters'])) $options['filters'] = array($options['filters']);

		$ac =& $this->scriptAssetCollection;
		$aw =& $this->scriptAssetWriter;

		// Resolve filters
		$filters = $this->resolveFilters($options['filters']);

		foreach($source as $leaf) {
			$leaf = self::guessExtension($leaf, $options);
			if(strpos($leaf, '*')) $asset = new GlobAsset(JS . $leaf, $filters);
			else $asset = new FileAsset(JS . $leaf, $filters);
			$ac->add($asset);

			if(!static::$config['optimize']) {
				if((!empty($filters) || get_class($asset) == 'Assetic\Asset\GlobAsset')) {
					$leaf = $options['target'] ?: self::normalizeExtension($leaf, $options['type']);
					$asset->setTargetPath($leaf);
					$aw->writeAsset($asset);
				}
				echo $this->Html->script($leaf) . "\n\t";
			}
		}

		return null;

	}

	public function scripts($options = array()) {

		if(!static::$config['optimize']) {
			return null;
		}

		$defaults = array(
			'target' => "main",
			'type' => "script",
			'force' => false,
			'version' => false,
			'filters' => array()
		);
		$options += $defaults;

		// Ensure arrays
		if(!is_array($options['filters'])) $options['filters'] = array($options['filters']);

		$ac =& $this->scriptAssetCollection;
		$am =& $this->scriptAssetManager;
		$aw =& $this->scriptAssetWriter;

		// Resolve filters
		$filters = $this->resolveFilters($options['filters']);
		foreach($filters as $filter) {
			$ac->ensureFilter($filter);
		}


		$options['target'] = self::normalizeExtension($options['target'], $options['type']);
		$ac->setTargetPath($options['target']);
		$am->set(str_replace('.', '', $options['target']), $ac);

		//echo $ac->dump(); exit;
		// Write static assets
		if($options['force'] || !is_file(static::$config['scriptsPath'] . DS . $options['target'])) {
			$aw->writeManagerAssets($am);
		}

		return $this->Html->script($options['target'] . ($options['version'] ? '?' . $options['version'] : ''), array('inline' => true));

	}

	private function resolveFilters($filters) {

		$fm =& static::$filterManager;

		$resolvedFilters = array();
		foreach($filters as $filter) {
			if(!$fm || !$fm->has($filter)) throw new \BadRequestException(sprintf('Filter `%s` has not been configured.', $filter));
			$resolvedFilters[] = $fm->get($filter);
		}

		return $resolvedFilters;

	}

	private function registerFilters($filters) {
		if(!is_array($filters)) $filters = array($filters);

		$fm =& static::$filterManager;
		if(!$fm) $fm = new FilterManager();

		foreach($filters as $key => $filter) {
			static::$filterManager->set($key, $filter);
		}

	}

	private static function guessExtension($leaf, $options = array()) {

		if(empty($options['type'])) return $leaf;

		if($options['type'] == 'style') {
			if(preg_match('/(.css|.less|.sass|.scss)$/is', $leaf)) return $leaf;
			else if(in_array('less', $options['filters']) || in_array('lessphp', $options['filters'])) $leaf .= '.less';
			else if(in_array('sass', $options['filters'])) $leaf .= '.scss';
			else $leaf .= '.css';
		} else if($options['type'] == 'script') {
			if(preg_match('/(.js)$/is', $leaf)) return $leaf;
			$leaf .= '.js';
		}

		return $leaf;

	}

	private static function normalizeExtension($leaf, $type) {

		if($type == 'style') {
			$leaf = preg_replace('/([^\.]+)(.css|.less|.sass|.scss)?$/is', '$1.css', $leaf);
		} else if($type == 'script') {
			$leaf = preg_replace('/([^\.]+)(.js)?$/is', '$1.js', $leaf);
		}

		$leaf = str_replace('*', 'combined', $leaf);

		return $leaf;

	}


}
