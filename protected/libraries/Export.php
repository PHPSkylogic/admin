<?php
/**
 * Created by PhpStorm.
 * User: follower
 * Date: 3/12/14
 * Time: 8:51 PM
 */

class Export {

    /*
     * Exports array to CSV file
     * @params array
     */

    static public function exportCSV (array &$fields)
    {
        $delimiter = ';';
        $output = '';
        $tmp = array();

        foreach($fields[0] as $key=>$val) {
            $tmp[] = $key;
        }
        $output .= implode($delimiter, $tmp).PHP_EOL;

        foreach($fields as $row) {

            $tmp = array();
            foreach ($row as $field) {
                    $tmp[] = $field;
            }

            $output .= mb_convert_encoding(implode($delimiter, $tmp), 'WINDOWS-1251', 'UTF-8').PHP_EOL;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=export.csv');

        echo $output;
        exit();
    }


    /*
     * Exports array to HTML file
     * @params array
     */

    static public function exportHTML (array &$fields)
    {

        $header = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
                "http://www.w3.org/TR/html4/loose.dtd">
                <head>
                    <meta http-equiv="Content-type" content="text/html;charset=UTF-8">
                    <title>Export File - Skylogic CMS</title>
                </head>
                <body>';

        $table = '<table width="100%" cellspacing="0" border="1">
                    <thead><tr>';

        foreach($fields[0] as $key=>$val) {
            $table.= '<td>'.$key.'</td>';
        }

        $table .= '</tr></thead>';
        $table .= '<tbody>';

        foreach($fields as $row) {

            $table .= '<tr>';

            foreach($row as $cell) {
                $table .= '<td>'.$cell.'</td>';
            }
            $table .= '</tr>';
        }
        $table .= '</tbody></table>';
        $footer = '</body></html>';

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename=export.html');

        echo $header.$table.$footer;
        exit();
    }


    /*
     * Exports array to XLS file
     * @params array
     */

    static public function exportXLS (array &$fields)
    {
        $titles = '';
        $data = '';

        $output = file_get_contents(LIBRARY.'template.xml');

        $fieldsCount = 0;
        foreach($fields[0] as $key=>$val) {
            $titles .= '<ss:Cell  ss:StyleID="s27"><Data ss:Type="String">'.$key.'</Data></ss:Cell>'.chr(10);
            $fieldsCount++;
        }

        foreach($fields as $row) {
            $data .= '<ss:Row>'.chr(10);
            foreach($fields[0] as $key=>$val) {
                (is_numeric($row[$key])) ? $p = 'Number' : $p = 'String';
                $data .= '<ss:Cell><Data ss:Type="'.$p.'">' . $row[$key] . '</Data></ss:Cell>'.chr(10);
            }
            $data .= '</ss:Row>'.chr(10);
        }

        $output = str_replace('{columns}', $titles, $output);
        $output = str_replace('{data}', $data, $output);

        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=export.xls");

        echo $output;
        exit();
    }


    /*
     * Exports array to PDF file
     * @params array
     */

    static public function exportPDF (array &$fields)
    {
        echo '@TODO';
    }
} 