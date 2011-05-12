<?php
/*
Plugin Name: ic BeSocial
Plugin URI: http://wordpress.org/extend/plugins/ic-besocial/
Description: Genera botones para el envío o la votación en distintas redes sociales: Facebook, Twitter, Google Buzz, Delicious, Reddit, Meneame, Bitacoras.com, Divúlgame y Divoblogger. Opcionalmente puede mostrar contadores con el número de votos o veces que se ha compartido (según la red).
Author: Jose Cuesta
Version: 2.0
Author URI: http://www.inerciacreativa.com/
*/

/*  This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class ic_Plugin {

	var $defaults	= array();
	var $options	= array();

	var $id			= '';
	var $name		= '';

	function ic_Plugin() {
		$this->init();
	}

	function init() {
		$this->id = strtolower(get_class($this));
		$this->name = end(explode('_', $this->id));

		$this->loadOptions();
	}

	function addOption( $name, $option ) {
		$default = array(
			'permanent'	=> true,
			'export'	=> false,
			'type'		=> 'text',
			'value'		=> '',
			'label'		=> '',
			'info'		=> '',
			'enum'		=> null
		);
		$option = wp_parse_args($option, $default);
		$this->defaults[$name] = $option;
	}

	/**
	 * Establece el valor de una opción
	 *
	 * @param string $name Nombre de la opción
	 * @param int|string|array|object $value Valor de la opción
	 * @return bool Devuelve true si no hay error, false en caso contrario
	 */
	function setOption( $name, $value ) {
		if ( isset($this->defaults[$name]) ) {
			settype($value, gettype($this->defaults[$name]['value']));
			$this->options[$name] = $value;
			return true;
		}

		return false;
	}

	/**
	 * Devuelve el valor de una opción
	 *
	 * @param string $name Nombre de la opción
	 * @return int|string|array|object|null Devuelve el valor o null si no existe
	 */
	function getOption( $name ) {
		$value = null;

		if ( isset($this->options[$name]) ) {
			$value = $this->options[$name];
		} elseif ( isset($this->defaults[$name]['value']) ) {
			$value = $this->defaults[$name]['value'];
		}

		return $value;
	}

	/**
	 * Carga las opciones
	 */
	function loadOptions() {
		$this->options = get_option($this->id);

		if ( empty($this->options) ) {
			$this->options = array();
		}
	}

	/**
	 * Guarda las opciones de la página de configuración
	 */
	function saveOptions() {
		foreach ( $this->defaults as $name => $option ) {
			if ( $option['permanent'] ) {
				$value = isset($_POST[$this->name][$name]) ? $_POST[$this->name][$name] : '';
				$this->setOption($name, $value);
			}
		}

		return update_option($this->id, $this->options);
	}

	function getControls() {
		echo '<h3 style="border-top:1px dotted #CCC">', ucfirst($this->name), '</h3>';
		echo '<table class="form-table">', "\n";

		foreach ( $this->defaults as $name => $option ) {
			if ( $option['permanent']) {
				$this->getControl($name, $this->getOption($name), $option['type'], $option['label'], $option['info'], $option['enum']);
			}
		}

		echo '</table>', "\n";
	}

	function getControl( $option, $value, $type, $label = '', $info = '', $enum = null ) {
		if ( $type == 'text' ) {
			$extra = 'class="regular-text"';
			$value = esc_attr($value);
		} elseif ( $type == 'checkbox' ) {
			$extra = ($value) ? 'checked="checked"' : '';
			$value = '1';
		} elseif ( $type == 'hidden' ) {
			$extra = '';
		} elseif ( $type == 'select' ) {
			$extra = '';
		}

		if ( isset($extra) ) {
			if ( $type != 'hidden' ) {
				echo '<tr valign="top">';
				if ( $label ) {
					printf('<th scope="row"><label for="%s-%s">%s</label></th>', $this->name, trim($option, '_'), $label);
				}
				echo '<td>';
			}

			if ( $type != 'select' ) {
				printf('<input id="%1$s-%2$s" name="%1$s[%3$s]" type="%4$s" value="%5$s" %6$s/>', $this->name, trim($option, '_'), $option, $type, $value, $extra);
			} else {
				printf('<select id="%1$s-%2$s" name="%1$s[%3$s]">', $this->name, trim($option, '_'), $option);
				foreach ( $enum as $key => $val ) {
					$selected = ($key == $value) ? ' selected="selected"' : '';
					printf('<option value="%s"%s>%s </option>', $key, $selected, $val);
				}
				printf('</select>');
			}

			if ( $type != 'hidden' ) {
				if ( $info ) {
					printf(' <span class="description">%s</span>', $info);
				}
				echo '</td></tr>';
			}

			echo "\n";
		}
	}
}

class ic_BeSocial extends ic_Plugin {

	var $version	= '2.0';
	var $buttons	= array('meneame', 'divulgame', 'divoblogger', 'bitacoras', 'reddit', 'delicious', 'buzz', 'facebook', 'twitter');
	var $objects	= array();

	var $active		= false;
	var $mode		= '';

	var $path		= '';
	var $uri		= '';

	var $devel		= false;

	function init() {
		parent::init();

		$this->path = plugin_basename(dirname(__FILE__));
		$this->uri = plugins_url($this->path);

		load_plugin_textdomain('besocial', false, $this->getPath('lang'));

		add_action('admin_menu', array(&$this, 'initAdmin'));
		add_action('wp', array(&$this, 'initButtons'), 20);

		$this->addOption('styles', array('label' => __('Use default styles', 'besocial'), 'type' => 'checkbox', 'value' => 1, 'info' => __('Uncheck if you want to style the buttons by yourself', 'besocial')));
		$this->addOption('position', array('label' => __('Position', 'besocial'), 'type' => 'select', 'value' => 'bottom', 'enum' => array('bottom' => __('Bottom', 'besocial'), 'top' => __('Top', 'besocial'), 'none' => __('None', 'besocial')), 'info' => __('If "None" is selected you must use <code>ic_BeSocial_Buttons()</code> to show the buttons', 'besocial')));
		$this->addOption('alignment', array('label' => __('Alignment', 'besocial'), 'type' => 'select', 'value' => 'center', 'enum' => array('center' => __('Center', 'besocial'), 'left' => __('Left', 'besocial'), 'right' => __('Right', 'besocial'))));
		$this->addOption('posts', array('label' => __('Show on posts', 'besocial'), 'type' => 'select', 'value' => 'counters', 'enum' => array('counters' => __('Show with counters', 'besocial'), 'show' => __('Show', 'besocial'), 'hide' => __('Hide', 'besocial'))));
		$this->addOption('pages', array('label' => __('Show on pages', 'besocial'), 'type' => 'select', 'value' => 'counters', 'enum' => array('counters' => __('Show with counters', 'besocial'), 'show' => __('Show', 'besocial'), 'hide' => __('Hide', 'besocial'))));
		$this->addOption('index', array('label' => __('Show on index', 'besocial'), 'type' => 'select', 'value' => 'show', 'enum' => array('show' => __('Show', 'besocial'), 'hide' => __('Hide', 'besocial'))));
		$this->addOption('archive', array('label' => __('Show on archives', 'besocial'), 'type' => 'select', 'value' => 'show', 'enum' => array('show' => __('Show', 'besocial'), 'hide' => __('Hide', 'besocial'))));
		//$this->addOption('feed', array('label' => __('Show on feed', 'besocial'), 'type' => 'select', 'value' => 'show', 'enum' => array('show' => __('Show', 'besocial'), 'hide' => __('Hide', 'besocial'))));
	}

	function getPath( $file ) {
		return $this->path . '/' . $file;
	}

	function getURI( $file ) {
		return $this->uri . '/' . $file;
	}

	function &getButton( $name ) {
		$name = strtolower($name);

		if ( !isset($this->objects[$name]) ) {
			$class = __CLASS__ . '_' . ucfirst($name);
			$this->objects[$name] =& new $class();
			return $this->objects[$name];
		}

		return $this->objects[$name];
	}

	/**
	 * Añade la página de configuración en el panel de administración
	 */
	function initAdmin() {
		add_options_page('ic BeSocial', 'ic BeSocial', 'manage_options', $this->getPath(basename(__FILE__)), array(&$this, 'getForm'));
	}

	/**
	 * Muestra la página de configuración de las opciones
	 */
	function getForm() {
		echo "<div class='wrap'>\n";
		printf("<h2>%s ic BeSocial V%s</h2>\n", __('Configure', 'besocial'), $this->version);

		if ( isset($_POST['submit']) && $this->setForm() ) {
			printf("<div id='message' class='updated fade'><p><strong>%s</strong></p></div>\n", __('Options saved', 'besocial'));
		}

		printf("<form name='settings' method='post' action='%s?page=%s'>\n", $_SERVER['PHP_SELF'], $this->getPath(basename(__FILE__)));
		wp_nonce_field($this->id);

		$this->getControls();
		foreach ( $this->buttons as $name ) {
			$button =& $this->getButton($name);
			$button->getControls();
		}

		printf("<p class='submit'><input class='button-primary' type='submit' value='%s' name='submit' /></p>\n", __('Update', 'besocial'));
		echo "</form>\n</div>\n";
	}

	/**
	 * Guarda las opciones
	 */
	function setForm() {
		check_admin_referer($this->id);

		$update = $this->saveOptions();

		foreach ( $this->buttons as $name ) {
			$button =& $this->getButton($name);
			$update = $button->saveOptions() || $update;
		}

		return $update;
	}

	function isActive() {
		$mode = false;

		if ( is_single() ) {
			$mode = 'posts';
		} elseif ( is_page() ) {
			$mode = 'pages';
		} elseif ( is_home() ) {
			$mode = 'index';
		} elseif ( is_archive() ) {
			$mode = 'archive';
		} elseif ( is_feed() ) {
			//$mode = 'feed';
		}

		if ($mode) {
			$this->mode = $this->getOption($mode);

			if ($this->mode !== 'hide') {
				$this->active = true;
			}
		}

		return $this->active;
	}

	/**
	 * Inicializa el plugin
	 */
	function initButtons() {
		if ( $this->isActive() ) {
			//if ( is_feed() ) {
				//add_action('the_content_feed', array(&$this, 'showButtons'), 20);
			//} else {
				add_action('the_content', array(&$this, 'showButtons'), 20);
			//}

			$this->getStyles();

			if ( $this->mode === 'counters' ) {
				the_post();

				$vars = array();
				$post = $this->getPost();

				foreach ( $this->buttons as $name ) {
					$button =& $this->getButton($name);
					$button->setPost($post, false);
					$vars = array_merge($vars, $button->toJS($post));
				}

				$this->getScripts($vars);

				rewind_posts();
			}
		}
	}

	/**
	 * Obtiene los datos necesarios del post
	 */
	function getPost() {
		global $post;

		$data = array();

		if ( $post->post_status == 'publish' && $post->post_password == '' ) {
			$data = array(
				'ID'	=> $post->ID,
				'title'	=> $post->post_title,
				'url'	=> get_permalink()
			);
		}

		return $data;
	}

	/**
	 * Inserta las hojas de estilos
	 *
	 * @param string $source URL relativa al plugin
	 */
	function getStyles() {
		global $wptouch_plugin;

		if ( $this->getOption('styles') ) {
			$file = 'besocial.css';
			$devel = ($this->devel) ? 'devel/' : '';

			/*
			if ( is_a($wptouch_plugin, 'WPtouchPlugin') && $wptouch_plugin->desired_view == 'mobile' ) {
				$file = 'besocial-mobile.css';
			}
			*/

			wp_enqueue_style('besocial', $this->getURI($devel . $file), false, $this->version, 'all');
		}
	}

	/**
	 * Inserta el script para mostrar los contadores
	 *
	 * @param string $source URL relativa al plugin
	 */
	function getScripts( $vars ) {
		global $wp_scripts;

		$devel = ($this->devel) ? 'devel/' : '';
		wp_enqueue_script('besocial', $this->getURI($devel . 'besocial.js'), array(), $this->version, true);
		wp_localize_script('besocial', 'BeSocial', $vars);
	}

	/**
	 * Inserta los botones al final del contenido del post
	 *
	 * @param string $content El contenido del post
	 */
	function showButtons( $content = '', $manual = false ) {
		global $wp_query, $wp_the_query;

		if ( $manual || ($wp_query === $wp_the_query) ) {
			$position = $this->getOption('position');
			$alignment = $this->getOption('alignment');

			if ( (($position != 'none') && ($manual == false)) || (($position == 'none') && ($manual == true)) ) {
				$buttons = '';
				foreach ( $this->buttons as $name ) {
					$button =& $this->getButton($name);
					$button->setPost($this->getPost());
					$buttons .= $button->toHTML('<li>', '</li>');
				}

				if ( !empty($buttons) ) {
					if ( $position == 'top' ) {
						$content = '<div class="besocial"><ul class="' . $alignment . '">' . $buttons . '</ul></div>' . "\n" . $content;
					} else {
						$content .= "\n" . '<div class="besocial"><ul class="' . $alignment . '">' . $buttons . '</ul></div>';
					}
				}
			}
		}

		return $content;
	}
}

class ic_BeSocial_Button extends ic_Plugin {

	var $href		= '';
	var $text		= '';
	var $title		= '';
	var $count		= 0;

	function ic_BeSocial_Button() {
		$this->init();
	}

	function init() {
		parent::init();

		$this->addOption('active', array('label' => __('Active', 'besocial'), 'type' => 'checkbox', 'value' => 1));
	}

	function initButton( $post ) {}

	function setPost( $post, $count = true ) {
		if ( $this->isActive() ) {
			$this->initButton($post);
			if ( $count ) {
				++$this->count;
			}
		}
	}

	function setHref( $href ) {
		$this->href = $href;
	}

	function setText( $text ) {
		$this->text = $text;
	}

	function setTitle( $title ) {
		$this->title = $title;
	}

	function isActive() {
		return $this->getOption('active');
	}

	function isReady() {
		return !empty($this->href);
	}

	function toHTML( $before = '', $after = '' ) {
		if ( $this->isReady() ) {
			$html = sprintf('<a class="besocial-%s" id="besocial-%s-%d" rel="nofollow" href="%s" title="%s"><span class="besocial-text">%s</span></a>', $this->name, $this->name, $this->count, $this->href, $this->title, $this->text);
			return $before . $html . $after;
		}

		return '';
	}

	function toJS() {
		$vars = array();

		if ( $this->isReady() ) {
			foreach ( $this->defaults as $name => $option ) {
				if ( $option['export'] ) {
					$vars[$this->name . '_' . $name] = $this->getOption($name);
				}
			}
		} else {
			$vars[$this->name . '_active'] = '0';
		}

		return $vars;
	}

	/**
	 * Realiza una petición HTTP y devuelve el cuerpo de la respuesta
	 *
	 * @param string $url URL que se quiere recibir
	 */
	function getRemote( $url ) {
		$response = wp_remote_request($url);

		if ( is_array($response) && ($response['response']['code'] >= 200) && ($response['response']['code'] < 300) ) {
			return $response['body'];
		}

		return '';
	}

	/**
	 * Devuelve el valor de un elemento de una cadena o documento XML
	 *
	 * @param string $element Nombre del elemento
	 * @param string $xml Cadena XML
	 * @param string $default Valor por defecto si no se encuentra el elemento
	 */
	function getElement( $element, $xml, $default = '' ) {
		$value = $default;
		$pattern = is_int($default) ? '\d' : '.';
		$matches = array();

		if ( preg_match("/<$element>($pattern+)<\/$element>/", $xml, $matches) ) {
			$value = is_int($default) ? intval($matches[1]) : $matches[1];
		}

		return $value;
	}
}

class ic_BeSocial_Twitter extends ic_BeSocial_Button {

	function init() {
		parent::init();

		add_action('admin_menu', array(&$this, 'initAdmin'));

		$this->addOption('user', array('label' => __('Twitter username', 'besocial')));
		$this->addOption('login', array('label' => __('Bit.ly username', 'besocial'), 'value' => 'retweetjs'));
		$this->addOption('apikey', array('label' => __('Bit.ly API key', 'besocial'), 'value' => 'R_6287c92ecaf9efc6f39e4f33bdbf80b1', 'info' => __('Found here:', 'besocial') . ' <a href="http://bit.ly/account/your_api_key" target="_blank">http://bit.ly/account/your_api_key</a>'));
		$this->addOption('url', array('permanent' => false, 'export' => true));
	}

	function initButton( $post ) {
		if ( $url = $this->getShortURL($post['ID'], $post['url']) ) {
			$this->setText('Twitter');
			$this->setTitle(__('Retweet this', 'besocial'));
			$this->setHref('http://twitter.com/home?status=' . rawurlencode((($this->getOption('user')) ? 'RT @' . $this->getOption('user') . ' ' : '') . $post['title'] . ' ' . $url));
			$this->setOption('url', $url);
		}
	}

	/**
	 * Devuelve la URL corta proporcionada por bit.ly
	 *
	 * @param int $post_ID El ID del post
	 * @param string $long Enlace permanente del post
	 */
	function getShortURL( $post_ID, $long ) {
		$short = get_post_meta($post_ID, '_' . $this->id, true);
		if ( empty($short) ) {
			if ( $response = $this->getAPI('shorten', 'longUrl', rawurlencode($long)) ) {
				if ( $short = $this->getElement('shortUrl', $response) ) {
					update_post_meta($post_ID, '_' . $this->id, $short);
				}
			}
		}

		return $short;
	}

	/**
	 * Genera una llamada a la API de bit.ly
	 *
	 * @param string $method El método a ejecutar
	 * @param string $param Nombre del parámetro
	 * @param string $value Valor del parámetro
	 */
	function getAPI( $method, $param, $value ) {
		if ( $this->getOption('login') && $this->getOption('apikey') ) {
			return $this->getRemote('http://api.bit.ly/' . $method . '?version=2.0.1&login=' . strtolower($this->getOption('login')) . '&apiKey=' . $this->getOption('apikey') . '&format=xml&' . $param . '=' . $value);
		}

		return '';
	}

	function initAdmin() {
		add_meta_box('besocial', 'ic BeSocial', array(&$this, 'getMetabox'), 'post');
	}

	/**
	 * Inserta una meta caja en la pantalla de edición de posts con la URL corta de bit.ly
	 */
	function getMetabox() {
		global $post;

		$short = '';
		if ( $post->post_status == 'publish' ) {
			$short = $this->getShortURL($post->ID, get_permalink());
			if (!$short) {
				$short = 'Error!';
			}
		}

		echo '<label for="besocial-shorturl">', __('Bit.ly URL', 'besocial'), '</label> ';
		echo '<input id="besocial-shorturl" readonly="readonly" type="text" value="', $short, '" />';
	}
}

class ic_BeSocial_Facebook extends ic_BeSocial_Button {

	function init() {
		parent::init();
	}

	function initButton( $post ) {
		$this->setText('Facebook');
		$this->setTitle(__('Share this on', 'besocial') . ' Facebook');
		$this->setHref('http://www.facebook.com/sharer.php?u=' . rawurlencode($post['url'])  . '&amp;t=' . rawurlencode($post['title']) . '&amp;src=sp');
	}
}

class ic_BeSocial_Bitacoras extends ic_BeSocial_Button {

	function init() {
		parent::init();

		$this->addOption('apikey', array('label' => __('API key', 'besocial'), 'value' => '5SDMPMTU4NROQVJNPMO65K21AZLXHIXZ', 'info' => __('Found here:', 'besocial') . ' <a href="http://bitacoras.com/dev/key" target="_blank">http://bitacoras.com/dev/key</a>', 'export' => true));
	}

	function initButton( $post ) {
		$this->setText('Bitacoras');
		$this->setTitle(__('Submit this to', 'besocial') . ' Bitacoras.com');
		$this->setHref('http://bitacoras.com/anotaciones/' . preg_replace('/^https?:\/\//', '', $post['url']));
	}
}

class ic_BeSocial_Meneame extends ic_BeSocial_Button {

	function init() {
		parent::init();
	}

	function initButton( $post ) {
		$this->setText('Meneame');
		$this->setTitle(__('Submit this to', 'besocial') . ' Meneame');
		$this->setHref('http://www.meneame.net/submit.php?url=' . $post['url'] . '&amp;title=' . rawurlencode($post['title']));
	}
}

class ic_BeSocial_Divulgame extends ic_BeSocial_Button {

	function init() {
		parent::init();
	}

	function initButton( $post ) {
		$this->setText('Divúlgame');
		$this->setTitle(__('Submit this to', 'besocial') . ' Divúlgame');
		$this->setHref('http://www.divulgame.net/submit.php?url=' . $post['url'] . '&amp;title=' . rawurlencode($post['title']));
	}
}

class ic_BeSocial_Divoblogger extends ic_BeSocial_Button {

	function init() {
		parent::init();
	}

	function initButton( $post ) {
		$this->setText('Divoblogger');
		$this->setTitle(__('Submit this to', 'besocial') . ' Divoblogger');
		$this->setHref('http://divoblogger.com/submit.php?url=' . $post['url'] . '&amp;title=' . rawurlencode($post['title']));
	}
}

class ic_BeSocial_Delicious extends ic_BeSocial_Button {

	function init() {
		parent::init();
	}

	function initButton( $post ) {
		$this->setText('Delicious');
		$this->setTitle(__('Bookmark this in', 'besocial') . ' Delicious');
		$this->setHref('http://www.delicious.com/save?v=5&amp;noui&amp;url=' . $post['url'] . '&amp;title=' . rawurlencode($post['title']));
	}
}

class ic_BeSocial_Reddit extends ic_BeSocial_Button {

	function init() {
		parent::init();
	}

	function initButton( $post ) {
		$this->setText('Reddit');
		$this->setTitle(__('Submit this to', 'besocial') . ' Reddit');
		$this->setHref('http://www.reddit.com/submit?url=' . $post['url'] . '&amp;title=' . rawurlencode($post['title']));
	}
}

class ic_BeSocial_Buzz extends ic_BeSocial_Button {

	function init() {
		parent::init();
	}

	function initButton( $post ) {
		$this->setText('Google Buzz');
		$this->setTitle(__('Share this on', 'besocial') . ' Google Buzz');
		$this->setHref('http://www.google.com/buzz/post?url=' . $post['url'] . '&amp;message=' . rawurlencode(get_bloginfo('name') . ': ' . $post['title']));
	}
}

function ic_BeSocial_Buttons() {
	global $ic_besocial;
	echo $ic_besocial->showButtons('', true);
}

$ic_besocial =& new ic_BeSocial();
?>
