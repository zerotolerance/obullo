<?php

Class Start extends Global_controller {
    
    function __construct()
    {   
        parent::__construct();
        
        loader::database();
        loader::base_helper('form');
        loader::base_helper('hmvc');
    }           
    
    public function index()
    {   
        ob_start();               
    
        $hmvc = hmvc_call('blog/blog/write/18282/', 0);
        $hmvc->set_post(array('test' => 'obullOyyyy'));
        echo $hmvc->exec();
            
        echo '<br /><br />';
            
        $hmvc2 = hmvc_call('blog/blog/read/4455', 0);
        echo $hmvc2->exec();
        
        // http://devzone.zend.com/article/2418

        $query = $this->db->query('SELECT * FROM articles');
        $num_rows = $query->row_count();
        
        echo 'PER PAGE: '.i_get_post('set_per_page').'<br />';
        
        $per_page = (i_get_post('set_per_page')) ? i_get_post('set_per_page') : '5';
        
        $params = array(
            'mode'         => 'sliding',  // jumping
            'per_page'     => $per_page,
            'delta'        => 2,
            'http_method'  => 'GET',    
            'url_var'      => 'page',
            'query_string' => TRUE,      // If FALSE use Obullo style URLs 
            'current_page' => $this->uri->segment(4),
            'base_url'     => '/obullo/index.php',
            'total_items'  => $num_rows,
            'extra_vars'   => array('d'=>'welcome','c'=>'start', 'm' => 'index', 'set_per_page' => $per_page),
        );
        
        $pager = pager::instance()->init($params);
         
        list($from, $to) = $pager->get_offset_by_page();
         
        echo 'from:'.$from.'<br />';
        echo 'to:'.$to.'<br />';
         
        $this->db->get('articles', $params['per_page'], $from - 1);
        $data = $this->db->fetch_all(assoc);
         
        $links = $pager->get_links();
        
        $hiddens = array(
        'd' => 'welcome',
        'c' => 'start',
        'm' => 'index',
        );
        
        echo form_open('', array('method' => $params['http_method']), $hiddens);
        echo $links['all'].'&nbsp;&nbsp; Per Page '.$pager->get_per_page_select_box(5, 50, 5, false).'&nbsp';
        echo form_submit('_send', 'Send', "");
        echo form_close();

        
        //Pager can also generate <link rel="first|prev|next|last"> tags
        // echo 'tags:'. $pager->linkTags.'<br /><br /><br />';

        //Show data for current page:
        print 'PAGED DATA: '.print_r($data).'<br /><br /><br />';
        
        //Results from methods:
        echo 'get_current_page()...: '; var_dump($pager->get_current_page());
        echo 'get_next_page()......: '; var_dump($pager->get_next_page());
        echo 'get_prev_page()......: '; var_dump($pager->get_prev_page());
        echo 'num_items()..........: '; var_dump($pager->num_items());
        echo 'num_pages()..........: '; var_dump($pager->num_pages());
        echo 'is_first_page()......: '; var_dump($pager->is_first_page());
        echo 'is_last_page().......: '; var_dump($pager->is_last_page());
        echo 'is_last_page_end()...: '; var_dump($pager->is_last_page_end());
        echo '$pager->range........: '; var_dump($pager->range);
        
        /*
        //Results from methods:
        echo 'get_current_page()...: '.var_dump($pager->get_current_page()).'<br />';
        echo 'get_next_page()......: '.var_dump($pager->get_next_page()).'<br />';
        echo 'get_prev_page()..: '.var_dump($pager->get_prev_page()).'<br />';
        echo 'num_items()...........: '.var_dump($pager->num_items()).'<br />';
        echo 'num_pages()...........: '.var_dump($pager->num_pages()).'<br />';
        echo 'is_first_page()........: '.var_dump($pager->is_first_page()).'<br />';
        echo 'is_last_page().........: '.var_dump($pager->is_last_page()).'<br />';
        echo 'is_last_page_end().: '.var_dump($pager->is_last_page_end()).'<br />';
        echo '$pager->range........: '.var_dump($pager->range).'<br />';
        */
        /*
        view_var('title', 'Welcome to Obullo Framework !');
        
        $data['var'] = 'This page generated by Obullo.';
        
        view_var('body', view('view_welcome', $data)); 
        view_app('view_base_layout'); 
        */
        
        echo benchmark_memory_usage();
        
        $this->output->append_output(ob_get_clean());
    }

    
    function send_form()
    {   
        view_var('title', 'Welcome to Obullo Framework !');
        
        $data['var'] = 'This page generated by Obullo.';
        
        view_var('body', view('view_welcome', $data)); 
        view_app('view_base_layout'); 
    }
    
    
}

/* End of file start.php */
/* Location: .application/welcome/controllers/start.php */