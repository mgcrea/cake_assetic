# CakePHP Assetic Plugin #
by `Olivier Louvignes`

## DESCRIPTION ##

This repository contains a [CakePHP](https://github.com/cakephp/cakephp) helper that wraps [Assetic](https://github.com/kriswallsmith/assetic) asset manager functionnality.

## SETUP ##

Using `Lithium Assetic Plugin` requires [Assetic](https://github.com/kriswallsmith/assetic).

1. Clone [Assetic](https://github.com/kriswallsmith/assetic) to the `app/librairies/_source/assetic` folder.

		mkdir Vendor/_source
		git submodule add https://github.com/kriswallsmith/assetic.git Vendor/_source/assetic

2. Symlink `app/librairies/Assetic` to `app/librairies/_source/assetic/src/Assetic`.

		ln -s _source/assetic/src/Assetic Vendor/Assetic

2. Clone this plugin to `Plugin/Assetic`

		git submodule add https://github.com/mgcrea/cake_assetic.git Plugin/Assetic

3. Load the plugin in your `Config/bootstrap.php` file :

		CakePlugin::load('Assetic');

4. Configure the helper in your `Config/bootstrap.php` file, for instance with:

		/**
		 * Assetic configuration
		 */

		App::uses('AsseticHelper', 'Assetic.View/Helper');

		App::import('Vendor', 'lessc', array('file' => 'lessphp' . DS . 'lessc.inc.php'));
		App::import('Vendor', 'Assetic/Filter/FilterInterface');
		App::import('Vendor', 'Assetic/Filter/LessphpFilter');
		use Assetic\Filter\LessphpFilter;

		App::import('Vendor', 'Assetic/Filter/Yui/BaseCompressorFilter');
		App::import('Vendor', 'Assetic/Filter/Yui/CssCompressorFilter');
		App::import('Vendor', 'Assetic/Filter/Yui/JsCompressorFilter');
		use Assetic\Filter\Yui;

		AsseticHelper::config(array(
			'filters' => array(
				'lessphp' => new LessphpFilter(),
				'yui_css' => new Yui\CssCompressorFilter(ROOT . '/tools/yuicompressor-2.4.7.jar'),
				'yui_js' => new Yui\JsCompressorFilter(ROOT . '/tools/yuicompressor-2.4.7.jar')
			)
		));

5. Use the assetic helper in your layout :

		// Regular call
		<?php echo $this->Assetic->script(array('libs/json2', 'libs/phonegap-1.2.0', 'libs/underscore', 'libs/mustache')); ?>
		// Use some filter (will be processed even in development mode)
		<?php echo $this->Assetic->css(array('mobile/core'), array('target' => 'mobile.css', 'filters' => array('lessphp'))); ?>
		// Use glob asset (will be processed even in development mode)
		<?php echo $this->Assetic->script(array('php/*.js'), array('target' => 'php.js'));

6. Make sure to end your layout with final (production only by default) configuration :

		<?php echo $this->Assetic->styles(array('target' => 'mobile.css', 'filters' => 'yui_css')); ?> // Will not overwrite existing compiled file by default
		<?php echo $this->Assetic->scripts(array('target' => 'mobile.js', 'filters' => 'yui_js', 'force' => true)); ?> // Will generated compiled output even if files exists

7. You can override compilation/filters activation with this line (like on top of your layout file), it is off by default in a `development` environment :

		<?php $this->assetic->config(array('optimize' => true)); ?> // Force activation in development environment


## BUGS AND CONTRIBUTIONS ##

Patches welcome! Send a pull request.

Post issues on [Github](http://github.com/mgcrea/cake_assetic/issues)

The latest code will always be [here](http://github.com/mgcrea/cake_assetic)

## LICENSE ##

Copyright 2012 Olivier Louvignes. All rights reserved.

The MIT License

Permission is hereby granted, free of charge, to any person obtaining a
copy of this software and associated documentation files (the "Software"),
to deal in the Software without restriction, including without limitation
the rights to use, copy, modify, merge, publish, distribute, sublicense,
and/or sell copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
DEALINGS IN THE SOFTWARE.
