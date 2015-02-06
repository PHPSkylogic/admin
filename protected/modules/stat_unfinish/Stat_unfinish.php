<?
class Stat_unfinish extends Model
{
    static $table='stat_unfinish';
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
        $bascket = $this->db->rows("SELECT
                                    date, SUM(amount) AS amount
                                    FROM `bascket`
                                    WHERE `date` BETWEEN '$date_start' AND '$date_end'

                                    GROUP BY date
                                    ORDER BY `date`");

        $vars['bascket_count']=0;
        $vars['bascket'] = "['Дата', 'Кол-во']";
        foreach($bascket as $row)
        {
            $date = date("d.m.Y", strtotime($row['date']));
            $vars['bascket'].=",['".$date."', ".$row['amount']."]";
            $vars['bascket_count']+=$row['amount'];
        }

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