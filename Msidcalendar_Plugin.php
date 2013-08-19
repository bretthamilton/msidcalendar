<?php


include_once('Msidcalendar_LifeCycle.php');

class Msidcalendar_Plugin extends Msidcalendar_LifeCycle {

    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            'MSIDBaseURL' => array(__('Base url to the MSI Calendar', 'my-awesome-plugin')),
            'CanSeeSubmitData' => array(__('Can See Submission data', 'my-awesome-plugin'),
                                        'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber', 'Anyone')
        );
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }

    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr > 1)) {
                    $this->addOption($key, $arr[1]);
                }
            }
			
			$this->updateOption('MSIDBaseURL','http://msid.ca/calendar/events/json/');
        }
    }

    public function getPluginDisplayName() {
        return 'MSIDCalendar';
    }

    protected function getMainPluginFileName() {
        return 'msidcalendar.php';
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
        //            `id` INTEGER NOT NULL");
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
    }


    /**
     * Perform actions when upgrading from version X to version Y
     * See: http://plugin.michael-simpson.com/?page_id=35
     * @return void
     */
    public function upgrade() {
    }

    public function addActionsAndFilters() {

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

        // Example adding a script & style just for the options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        //        if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
        //            wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
        //            wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        }


        // Add Actions & Filters
        // http://plugin.michael-simpson.com/?page_id=37


        // Adding scripts & styles to all pages
        // Examples:
        add_action( 'wp_enqueue_scripts', array( &$this, 'register_plugin_styles' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'register_plugin_scripts' ) );

        // Register short codes
        // http://plugin.michael-simpson.com/?page_id=39


        // Register AJAX hooks
        // http://plugin.michael-simpson.com/?page_id=41
		add_action( 'wp_ajax_fetch_events', array( &$this, 'fetch_events' ) );
		add_action( 'wp_ajax_nopriv_fetch_events', array( &$this, 'fetch_events' ) );// optional
		
		// Include the Ajax library on the front end
		add_action( 'wp_head', array( &$this, 'add_ajax_library' ) );
		
    }

	/*--------------------------------------------*
	 * Action Functions
	 *--------------------------------------------*/

	/**
	 * Adds the WordPress Ajax Library to the frontend.
	 */
	public function add_ajax_library() {
		
		$html = '<script type="text/javascript">';
			$html .= 'var msidajaxurl = "' . admin_url( 'admin-ajax.php' ) . '"';
		$html .= '</script>';
		
		echo $html;	
		
	} // end add_ajax_library

	/**
	 * Registers and enqueues plugin-specific styles.
	 */
	public function register_plugin_styles() {
		
		wp_enqueue_style('jquery-ui','http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css');
	
		wp_register_style( 'ive-read-this', plugins_url( 'ive-read-this/css/plugin.css' ) );
		wp_enqueue_style( 'ive-read-this' );
	
	} // end register_plugin_styles
	
	/**
	 * Registers and enqueues plugin-specific scripts.
	 */
	public function register_plugin_scripts() {
	
		wp_enqueue_script('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js', array('jquery'), '1.10.3');
		
		wp_register_script( 'ive-read-this', plugins_url( 'ive-read-this/js/plugin.js' ), array( 'jquery' ) );
		
		wp_enqueue_script( 'ive-read-this' );
		
		
	
	} // end register_plugin_scripts
	
	
	public function fetch_events() {
		// Don't let IE cache this request
		header("Pragma: no-cache");
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
	 
		header("Content-type: text/plain");
				
		$start =  date('Y-m-d',$_POST["start"]);
		$end = date('Y-m-d',$_POST["end"]);

		$url = 'http://msid.ca/calendar/events/json/?sustainability_month=true&start_date='.$start.'&end_date='.$end;
		
		$json = file_get_contents($url );
		$obj = json_decode($json);

		
		$events = array();
		
		foreach($obj->events as $event)
		{
			$calobj=array();
			
			$calobj['title']= $event->summary;
			
			$calobj['id'] = $event->id;
						
			$calobj['keywords']= $event->keywords;
			$calobj['allDay'] = empty($event->start_time);
			
			$calobj['start'] = date('c',strtotime($event->year.'-'.$event->month.'-'.$event->day.' '.date("H:i", strtotime($event->start_time))));
			$calobj['end'] = date('c',strtotime($event->year.'-'.$event->month.'-'.$event->day.' '.date("H:i", strtotime($event->end_time))));
			
			array_push($events, $calobj);	 
		}

		
 		echo json_encode($events);
		die();
	}

}
