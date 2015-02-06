<?
class Stat_amount extends Model
{
    static $table='stat_amount';
    static $name='Динамика сумм заказов';

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
        $vars['list'] = $this->db->rows("SELECT date_add, sum
                                         FROM `orders`
                                         WHERE `date_add` BETWEEN '$date_start' AND '$date_end'
                                         ORDER BY `date_add`");

        $orders_array = "['Дата', 'Кол-во']";

        $total_sum = 0;
        $order_count = array();
        foreach($vars['list'] as $Rows)
        {
            $i = 0;
            $i++;
            $date = date("d.m.Y", strtotime($Rows['date_add']));
            if(!isset($order_count[$date]))$order_count[$date] = $i;
            else $order_count[$date] = $order_count[$date] + $i;
        }


        if (sizeof($order_count) == 0)
        {
            $orders_array.=",\n[0, 0]";
        }
        else{
            foreach($order_count as $key=>$value)
            {
                $date = date("d.m.Y", strtotime($key));
                $sum = (int)$value;
                $orders_array.=",\n['{$date}', {$sum}]";
            }
        }

        $vars['total_sum'] = $total_sum;
        $vars['text'] = $orders_array;
        $vars['date_start'] = $date_start;
        $vars['date_end'] = $date_end;
        $vars['compare'] = $compare;

        if(!isset($this->registry['admin']))$this->registry->set('admin', $this->table);
        $view =  new View($this->registry);
        $content = $view->Render('graph.phtml', $vars);


        return $content;
    }

}
?>