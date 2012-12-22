<?php
/*
Plugin Name: ic BeSocial
Plugin URI: http://wordpress.org/extend/plugins/ic-besocial/
Description: Genera botones para el envío o la votación en distintas redes sociales.
Author: Jose Cuesta
Version: 3.0
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

class icBeSocial
{

    const VERSION = '3.0';
    const DEBUG = false;

    private $uri = '';
    private $buttons = array('Facebook', 'Twitter', 'Google+', 'Reddit', 'LinkedIn', 'Meneame');

    public function __construct()
    {
        if (is_admin()) {
            add_action('plugins_loaded', array($this, 'setDefaultSettings'));

            add_action('admin_init', array($this, 'registerSettings'));
            add_action('admin_menu', array($this, 'addSettingsPage'));
            add_filter('plugin_action_links', array($this, 'addSettingsLink'), 10, 2);
        } else {
            add_action('wp', array($this, 'enqueueAssets'), 20);
            add_action('the_content', array($this, 'addContainer'), 100);
        }
    }

    public function setDefaultSettings()
    {
        $options = get_option('ic-besocial', array());
        $update = false;

        if (!isset($options['tracking'])) {
            $options['tracking'] = true;
            $update = true;
        }

        if (!isset($options['stylesheet'])) {
            $options['stylesheet'] = true;
            $update = true;
        }

        if (!isset($options['buttons'])) {
            $options['buttons'] = array();
            $update = true;
        }

        foreach ($this->buttons as $button) {
            $name = trim(strtolower($button), '+');

            if (!isset($options['buttons'][$name])) {
                $options['buttons'][$name] = true;
                $update = true;
            }
        }

        if (!isset($options['twitter'])) {
            $options['twitter'] = '';
            $update = true;
        }

        if ($update) {
            update_option('ic-besocial', $options);
        }
    }

    public function addSettingsLink($links, $file)
    {
        if ($file == plugin_basename(__FILE__)) {
            $link = sprintf('<a href="options-general.php?page=ic-besocial">%s</a>', __('Settings', 'ic-besocial'));
            array_unshift($links, $link);
        }

        return $links;
    }

    public function addSettingsPage()
    {
        add_options_page('ic BeSocial', 'ic BeSocial', 'manage_options', 'ic-besocial', array($this, 'showSettings'));
    }

    public function registerSettings()
    {
        register_setting('ic-besocial', 'ic-besocial', array($this, 'checkSettings'));

        add_settings_section('ic-besocial_general', __('General', 'ic-besocial'), array(
            $this,
            'showGeneralText'
        ), 'ic-besocial');
        add_settings_field('ic-besocial_active-css', __('Active CSS', 'ic-besocial'), array(
            $this,
            'showDefaultStyle'
        ), 'ic-besocial', 'ic-besocial_general');
        add_settings_field('ic-besocial_active-ga', __('Active tracking', 'ic-besocial'), array(
            $this,
            'showAllowTracking'
        ), 'ic-besocial', 'ic-besocial_general');
        add_settings_field('ic-besocial_buttons-list', __('Active buttons', 'ic-besocial'), array(
            $this,
            'showButtonsList'
        ), 'ic-besocial', 'ic-besocial_general');

        add_settings_section('ic-besocial_twitter', __('Twitter', 'ic-besocial'), array(
            $this,
            'showTwitterText'
        ), 'ic-besocial');
        add_settings_field('ic-besocial_twitter-username', __('Username', 'ic-besocial'), array(
            $this,
            'showTwitterUsername'
        ), 'ic-besocial', 'ic-besocial_twitter');
    }

    public function checkSettings($settings)
    {
        $options = get_option('ic-besocial');

        $options['tracking']   = empty($settings['tracking']) ? false : true;
        $options['stylesheet'] = empty($settings['stylesheet']) ? false : true;
        $options['twitter']    = ltrim(trim($settings['twitter']), '@');

        foreach ($options['buttons'] as $button => $active) {
            $options['buttons'][$button] = empty($settings['buttons'][$button]) ? false : true;
        }

        return $options;
    }

    public function showSettings()
    {
        print('<div class="wrap">');
        printf('<h2>ic BeSocial V%s</h2>', self::VERSION);
        print('<form action="options.php" method="post">');
        settings_fields('ic-besocial');
        do_settings_sections('ic-besocial');
        submit_button();
        print('</form></div>');
    }

    public function showGeneralText()
    {
    }

    public function showAllowTracking()
    {
        $options = get_option('ic-besocial');
        $checked = checked($options['tracking'], true, false);

        $this->getCheckbox("[tracking]", $checked, __('Add social events in Google Analytics', 'ic-besocial'));
    }

    public function showDefaultStyle()
    {
        $options = get_option('ic-besocial');
        $checked = checked($options['stylesheet'], true, false);

        $this->getCheckbox("[stylesheet]", $checked, __('Use default stylesheet', 'ic-besocial'));
    }

    public function showButtonsList()
    {
        $options = get_option('ic-besocial');

        foreach ($this->buttons as $button) {
            $name    = trim(strtolower($button), '+');
            $checked = checked($options['buttons'][$name], true, false);

            $this->getCheckbox("[buttons][$name]", $checked, $button);
        }
    }

    public function showTwitterText()
    {
    }

    public function showTwitterUsername()
    {
        $options = get_option('ic-besocial');

        $this->getText('[twitter]', $options['twitter']);
    }

    public function enqueueAssets()
    {
        $this->uri = plugins_url(plugin_basename(dirname(__FILE__)));
        $options   = get_option('ic-besocial');
        $scripts   = array('jquery');
        $uri       = $this->uri . '/';

        if (self::DEBUG) {
            $uri .= 'devel/';
            wp_enqueue_script('jquery-besocial', $uri . 'jquery-besocial.js', $scripts, self::VERSION, true);
            array_push($scripts, 'jquery-besocial');
        }

        if ($options['stylesheet']) {
            wp_enqueue_style('ic-besocial', $uri . 'besocial.css', array(), self::VERSION);
        }

        wp_enqueue_script('ic-besocial', $uri . 'besocial.js', $scripts, self::VERSION, true);
    }

    public function addContainer($content)
    {
        if (is_feed() || is_page()) {
            return $content;
        }

        $options = get_option('ic-besocial');
        $attrs   = '';
        $data    = array(
            'url'   => get_permalink(),
            'base'  => $this->uri,
            'title' => get_the_title(),
            'track' => $options['tracking'] ? 'true' : 'false',
            'via'   => $options['twitter'],
            'show'  => json_encode($options['buttons'])
        );

        foreach ($data as $attr => $value) {
            $attrs .= sprintf('data-%s="%s" ', $attr, esc_attr($value));
        }

        $content .= sprintf('<div class="be-social" %s><h2 class="be-social-title">%s</h2></div>', $attrs, __('Share', 'ic-besocial'));

        return $content;
    }

    private function getText($name, $value)
    {
        printf('<input type="text" size="40" name="ic-besocial%s" value="%s">', $name, $value);
    }

    private function getCheckbox($name, $checked, $label)
    {
        printf('<label><input type="checkbox" name="ic-besocial%s"%s> %s</label><br>', $name, $checked, $label);
    }

}

$ic_besocial = new icBeSocial();

register_activation_hook(__FILE__, array($ic_besocial, 'setDefaultSettings'));

