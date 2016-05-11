<?php
/**
 * Created by PhpStorm.
 * User: dillenbourgjeremy
 * Date: 11/05/16
 * Time: 21:02
 */

namespace SearchExtension;

use \Illuminate\Database\Eloquent;

class SearchExtension extends Eloquent
{
    protected $search_query = null;

    public static function search($request) {

        $obj = (new static);

        $requestData = $obj->cleanSearchContent($obj, $request->all());

        $data = $obj::where(function($q) use ($obj, $requestData) {
            $obj->generateSearchContent($q,$requestData);
        });

        if($request->has('sortby')) {
            $data = $data->orderBy($request->get('sortby'), $request->get('sortbyorder', 'ASC'));
        }

        if($request->has('page')) {
            $data = $data->paginate( $request->get('per_page', \Config::get('paginate')));
        } else {
            $data = $data->get();
        }

        return $data;
    }

    public function generateSearchContent($q,$data) {
        if(is_null($this->search_query)) {
            $this->search_query = $q;
        }

        foreach($data as $k=>$v) {
            if($k == 'search') {
                foreach($v as $kk=>$vv) {
                    $operand = $vv['operator'];
                    $this->search_query = $this->search_query->{$this->getWhere($operand)}(function($q2) {
                        foreach($vv as $kkk=>$vvv) {
                            if(is_array($vvv)) {
                                $q2->$this->generateSearchContent($q2, $vvv);
                            }
                        }
                    });
                }
            } else {
                $this->search_query = $this->search_query->{$this->getWhere($v['operator'])}($k, $this->getCritere($v['critere']), $v['value']);
            }
        }
    }

    public function cleanSearchContent($obj, $data) {
        $fillable = $obj->getFillable();

        foreach($data as $k=>$v) {
            if( (!is_array($v) || !in_array($k, $fillable)) && $k != 'search') {
                unset($data[$k]);
            }
        }
        return $data;
    }

    public function getWhere($operand) {
        switch(strtolower($operand)) {
            case 'or':
                return 'orWhere';
                break;
            case 'and':
                return 'where';
                break;
        }
    }

    public function getCritere($critere) {
        switch(strtolower($critere)) {
            case 'eq':
                return '=';
                break;
            case 'neq':
                return '!=';
                break;
            case 'lt':
                return '<';
                break;
            case 'lte':
                return '<=';
                break;
            case 'gt':
                return '>';
                break;
            case 'gte':
                return '>=';
                break;
            case 'like':
                return 'LIKE';
                break;
        }
    }
}