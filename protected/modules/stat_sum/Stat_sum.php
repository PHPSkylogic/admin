<?
class Stat_sum extends Model
{
    static $table='stat_sum';
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

        $orders_sum_array = "['Дата', 'Сумма']";

        $total_sum = 0;
        $order_sum = array();
        foreach($vars['list'] as $Rows)
        {
            $date = date("d.m.Y", strtotime($Rows['date_add']));
            $sum = (int)$Rows['sum'];
            if(!isset($order_sum[$date]))$order_sum[$date] = $Rows['sum'];
            else $order_sum[$date] = $order_sum[$date] + $sum;
        }


        if(sizeof($order_sum) == 0)
        {
            $orders_sum_array.=",\n[0, 0]";
        }
        else{
            foreach($order_sum as $key=>$value)
            {
                $date = date("d.m.Y", strtotime($key));
                $sum = (int)$value;
                $orders_sum_array.=",\n['{$date}', {$sum}]";
                $total_sum += (int)$value;
            }
        }

        $vars['total_sum'] = $total_sum;
        $vars['text'] = $orders_sum_array;
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