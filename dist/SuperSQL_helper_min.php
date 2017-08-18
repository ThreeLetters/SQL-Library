<?php
/*
 Author: Andrews54757
 License: MIT (https://github.com/ThreeLetters/SuperSQL/blob/master/LICENSE)
 Source: https://github.com/ThreeLetters/SQL-Library
 Build: v1.0.5
 Built on: 18/08/2017
*/

namespace SuperSQL;

class SQLHelper{public$s;public$connections;function __construct($a,$b=null,$c=null,$d=null,$e=array()){$this->connections=array();if(is_array($a)){if(is_array($a[0])){foreach($a as$f=>$g){$h=isset($g['host'])?$g['host']: '';$b=isset($g['db'])?$g['db']: '';$c=isset($g['user'])?$g['user']: '';$d=isset($g['password'])?$g['password']: '';$i=isset($g['options'])?$g['options']: array();$j=self::connect($h,$b,$c,$d,$i);array_push($this->connections,$j);}}else{foreach($a as$f=>$g){array_push($this->connections,$g);}}$this->s=$this->connections[0];}else if(is_string($a)){$this->s=self::connect($a,$b,$c,$d,$e);array_push($this->connections,$this->s);}else{array_push($this->connections,$a);$this->s=$a;}}static function connect($h,$b,$c,$d,$e=array()){$k='mysql';$l=false;if(is_string($e)){if(strpos($e,':')!==false){$l=$e;}else{$k=strtolower($e);}}else if(isset($e['dbtype']))$k=strtolower($e['dbtype']);if(!$l){$m='';switch($k){case 'pgsql':$m='pgsql';$n=array('dbname'=>$b,'host'=>$h);if(isset($e['port']))$n['port']=$e['port'];break;case 'sybase':$m='dblib';$n=array('dbname'=>$b,'host'=>$h);if(isset($e['port']))$n['port']=$e['port'];break;case 'oracle':$m='oci';$n=array('dbname'=>isset($h)? '//'.$h.':'.(isset($e['port'])?$e['port']: '1521').'/'.$b :$b);break;default:$m='mysql';$n=array('dbname'=>$b);if(isset($e['socket']))$n['unix_socket']=$e['socket'];else{$n['host']=$h;if(isset($e['port']))$n['port']=$e['port'];}break;}$l=$m.':';if(isset($e['charset'])){$n['charset']=$e['charset'];}$l=$m.':';$o=0;foreach($n as$f=>$p){if($o!=0){$l.=';';}$l.=$f.'='.$p;$o++;}}return new SuperSQL($l,$c,$d);}private static function escape($q){$r=strtolower(gettype($q));if($r=='boolean'){$q=$q ? '1' : '0';}else if($r=='string'){$q='\''.$q.'\'';}else if($r=='double'||$r=='integer'){$q=(int)$q;}else if($r=='null'){$q='0';}return$q;}private static function escape2($q){if(is_numeric($q)){return(int)$q;}else{return '\''.$q.'\'';}}function change($s){$this->s=$this->connections[$s];return$this->s;}function getCon($t=false){if($t){return$this->connections;}else{return$this->s;}}function get($u,$v=array(),$w=array(),$x=null){$y=$this->s->SELECT($u,$v,$w,$x,1)->getData();return($y&&$y[0])?$y[0]: false;}function create($u,$n){$z='CREATE TABLE `'.$u.'` (';$aa=0;foreach($n as$f=>$p){if($aa!=0){$z.=', ';}$z.='`'.$f.'` '.$p;$aa++;}$z.=')';return$this->s->query($z);}function drop($u){return$this->s->query('DROP TABLE `'.$u.'`');}function replace($u,$n,$w=array()){$ba=array();foreach($n as$f=>$p){$ca='`'.Parser::rmComments($f).'`';foreach($p as$da=>$g){$ca='REPLACE('.$ca.', '.self::escape2($da).', '.self::escape($g).')';}$ba['#'.$f]=$ca;}return$this->s->UPDATE($u,$ba,$w);}function select($u,$v=array(),$w=array(),$x=null,$ea=false){return$this->s->SELECT($u,$v,$w,$x,$ea);}function insert($u,$n){return$this->s->INSERT($u,$n);}function update($u,$n,$w=array()){return$this->s->UPDATE($u,$n,$w);}function delete($u,$w=array()){return$this->s->DELETE($u,$w);}function sqBase($z,$w,$x){$fa=array();if($x){Parser::JOIN($x,$z);}if(count($w)!=0){$z.=' WHERE ';$z.=Parser::conditions($w,$fa);}$ga=$this->_query($z,$fa);return$ga[0]->fetchColumn();}function count($u,$w=array(),$x=array()){return$this->sqBase('SELECT COUNT(*) FROM `'.$u.'`',$w,$x);}function avg(){return$this->sqBase('SELECT AVG(`'.$column.'`) FROM `'.$u.'`',$w,$x);}function max($u,$ha,$w=array(),$x=array()){return$this->sqBase('SELECT MAX(`'.$ha.'`) FROM `'.$u.'`',$w,$x);}function min($u,$ha,$w=array(),$x=array()){return$this->sqBase('SELECT MIN(`'.$ha.'`) FROM `'.$u.'`',$w,$x);}function sum($u,$ha,$w=array(),$x=array()){return$this->sqBase('SELECT SUM(`'.$ha.'`) FROM `'.$u.'`',$w,$x);}function _query($z,$ia){$ja=$this->s->con->db->prepare($z);foreach($ia as$f=>&$ka){$ja->bindParam($f+1,$ka[0],$ka[1]);}$la=$ja->execute();return array($ja,$la);}function query($ma,$o=null){return$this->s->con->query($ma,$o);}function transact($na){return$this->s->transact($na);}function selectMap($u,$oa,$w=array(),$x=null,$ea=false){$v=array();$pa=array();function recurse($n,&$qa,&$v,&$pa){foreach($n as$f=>$p){if(is_int($f)){array_push($v,$p);$ra=Parser::getType($p);if($ra){$j=Parser::getType($p);if($j){$ra=$j;}else if($ra==="int"||$ra==="bool"||$ra==="string"||$ra==="json"||$ra==="obj"){$ra=false;}}if($ra){array_push($qa,$ra);}else{preg_match('/(?:[^\.]*\.)?(.*)/',$p,$sa);array_push($qa,$sa[1]);}}else{$qa[$f]=array();recurse($p,$qa[$f],$v,$pa);}}}recurse($oa,$pa,$v,$pa);$ta=$this->s->select($u,$v,$w,$x,$ea);$y=$ta->getData();function recurse2($n,$ua,&$va){$va=array();foreach($n as$f=>$p){if(is_int($f)){$va[$p]=$ua[$p];}else{recurse2($p,$ua,$va[$f]);}}}$ta->result=array();foreach($y as$aa=>$ua){recurse2($pa,$ua,$ta->result[$aa]);}return$ta;}}
?>