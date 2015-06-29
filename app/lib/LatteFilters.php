<?php
/**
 * Created by PhpStorm.
 * User: Peggy
 * Date: 29.6.2015
 * Time: 17:47
 */

namespace App;


use App\Model\Phone;
use Nette\Utils\DateTime;
use Nette\Utils\Html;

class LatteFilters {

    public static function month($month) {
        $months = array(
            '-',
            'leden',
            'únor',
            'březen',
            'duben',
            'květen',
            'červen',
            'červenec',
            'srpen',
            'září',
            'říjen',
            'listopad',
            'prosinec',
        );
        return isset($months[$month]) ? $months[$month] : $months[0];
    }

    public static function day(\DateTime $datetime) {
        $day = $datetime->format('N');
        $days=['Neděle','Pondělí','Úterý','Středa','Čtvrtek','Pátek','Sobota'];

        return @$days[$day];
    }

    public static function implode($array, $glue = ',') {
        return implode($glue, $array);
    }

    public static function date($date,$format='j.n.Y') {
        $date = DateTime::from($date);
        return $date->format($format);
    }

    public static function phone($phone) {
        if(!($phone instanceof Phone))
            $phone = new Phone($phone);

        return Html::el(null)->add( Html::el('small class="cc"')->setText($phone->getCc()) )->add( Html::el(null)->setText(" ".$phone->getNumber()) );
    }




    public static function bbcolumns($input) {

        // [one_third]textik[/one_third]

        $bb['one_third'] = '<div class="one_third bbcol">#</div>';
        $bb['one_third_last'] = '<div class="one_third column-last bbcol">#</div><div class="clear"></div>';
        $bb['two_third'] = '<div class="two_third bbcol">#</div>';
        $bb['two_third_last'] = '<div class="two_third column-last bbcol">#</div><div class="clear"></div>';
        $bb['one_half'] = '<div class="one_half bbcol">#</div>';
        $bb['one_half_last'] = '<div class="one_half column-last bbcol">#</div><div class="clear"></div>';
        $bb['one_fourth'] = '<div class="one_fourth bbcol">#</div>';
        $bb['one_fourth_last'] = '<div class="one_fourth column-last bbcol">#</div><div class="clear"></div>';
        $bb['three_fourth'] = '<div class="three_fourth bbcol">#</div>';
        $bb['three_fourth_last'] = '<div class="three_fourth column-last bbcol">#</div><div class="clear"></div>';
        $bb['one_fifth'] = '<div class="one_fifth bbcolh">#</div>';
        $bb['one_fifth_last'] = '<div class="one_fifth column-last bbcol">#</div><div class="clear"></div>';
        $bb['two_fifth'] = '<div class="two_fifth bbcol">#</div>';
        $bb['two_fifth_last'] = '<div class="two_fifth column-last bbcol">#</div><div class="clear"></div>';
        $bb['three_fifth'] = '<div class="three_fifth bbcol">#</div>';
        $bb['three_fifth_last'] = '<div class="three_fifth column-last bbcol">#</div><div class="clear"></div>';
        $bb['four_fifth'] = '<div class="four_fifth bbcol">#</div>';
        $bb['four_fifth_last'] = '<div class="four_fifth column-last bbcol">#</div><div class="clear"></div>';
        $bb['one_sixth'] = '<div class="one_sixth bbcol">#</div>';
        $bb['one_sixth_last'] = '<div class="one_sixth column-last bbcol">#</div><div class="clear"></div>';
        $bb['five_sixth'] = '<div class="five_sixth bbcol">#</div>';
        $bb['five_sixth_last'] = '<div class="five_sixth column-last bbcol">#</div><div class="clear"></div>';

        /** @see http://forrst.com/posts/Simple_PHP_BBCode_Parser-N0z */
        /*
                $match["b"] = "/\[b\](.*?)\[\/b\]/is";
                $replace["b"] = "<b>$1</b>";
                $match["i"] = "/\[i\](.*?)\[\/i\]/is";
                $replace["i"] = "<i>$1</i>";
        */

        $match = array();
        $replace = array();

        foreach($bb as $key => $replacing) {
            $match[$key] = "/\[$key\](.*?)\[\/$key\]/is";
            $replace[$key] = str_replace('#','$1',$replacing);
        }

        return preg_replace($match, $replace, $input);


    }
}