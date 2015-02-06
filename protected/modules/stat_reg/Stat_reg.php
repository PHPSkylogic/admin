<?
class Stat_reg extends Model
{
    static $table='stat_reg';
    static $name='Активность регистраций на сайте';

    public function __construct($registry)
    {
        parent::getInstance($registry);
    }

    public static function getObject($registry)
    {
        return new self::$table($registry);
    }


    public function viewGraph($date_start, $date_end, $compare='')
    {
        $count = $this->db->row("SELECT COUNT(*) AS cnt, start_date FROM `users`
                                 WHERE `start_date` <='".date("Y-m-d")."' ");

        $count2 = $this->db->row("SELECT COUNT(*) AS cnt, start_date FROM `users`
                                 WHERE `start_date` <='".date("Y-m-d", strtotime('-1 year'))."' ");
        $vars['count1']=$count['cnt'];
        $vars['count2']=$count2['cnt'];
        $orders_array = "['Дата', '1 период', '2 период']";
        for($i=12;$i>0;$i--)
        {
            $date1 = date("Y-m", strtotime('-'.$i.'month'));
            $count = $this->db->row("SELECT COUNT(*) AS cnt, start_date FROM `users`
                                 WHERE `start_date` BETWEEN '{$date1}-01 00:00:01' AND '{$date1}-31 23:59:59' ");

            $vars['count1']+=$count['cnt'];
            $date = date("Y-m", strtotime('-'.$i.'month -1 year'));


            $count = $this->db->row("SELECT COUNT(*) AS cnt, start_date FROM `users`
                                 WHERE `start_date` BETWEEN '{$date}-01 00:00:01' AND '{$date}-31 23:59:59' ");

            $vars['count2']+=$count['cnt'];
            $date = date("F", strtotime($date1));
            $orders_array.=",\n['{$date}', {$vars['count1']}, {$vars['count2']}]";

        }

        $vars['text'] = $orders_array;
        $vars['date_start'] = $date_start;
        $vars['date_end'] = $date_end;
        $vars['compare'] = 1;

        if(!isset($this->registry['admin']))$this->registry->set('admin', $this->table);
        $view =  new View($this->registry);
        $content = $view->Render('graph.phtml', $vars);


        return $content;
    }

}
?>