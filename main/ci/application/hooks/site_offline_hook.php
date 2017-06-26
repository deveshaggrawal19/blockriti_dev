&lt;

<?php  
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Check whether the site is offline or not.
*
*/
class site_offline_hook {

    public function __construct()
    {
    log_message('debug','Accessing site_offline hook!');
    }


    public function is_offline()
{
    if(file_exists(APPPATH.'config/config.php'))
    {
        include (APPPATH.'config/config.php');

        if(isset($config['is_offline']) && $config['is_offline']===FALSE)
        {
        $this->show_site_offline();
        exit;
        }
    }   
    }
    
    private function show_site_offline()
    {
    echo '&lt;html&gt;&lt;body>Due to maintenance this site is offline.&lt;/body&gt;&lt;/html>';
    }

}
/* Location: ./system/application/hooks/site_offline_hook.php */
