<?php
/*
 Author: Andrews54757
 License: MIT (https://github.com/ThreeLetters/SuperSQL/blob/master/LICENSE)
 Source: https://github.com/ThreeLetters/SQL-Library
 Build: v1.1.5
 Built on: 18/09/2017
*/

namespace SuperSQL;

// lib/connector.php
class Response implements \ArrayAccess,\Iterator{public$result;public$affected;public$ind=0;public$error=false;public$outTypes;public$complete=true;function __construct($a,$b,$c,$d){if(!$b){$this->error=$a->errorInfo();}else{$this->outTypes=$c;if($d===0){$e=$a->fetchAll(\PDO::FETCH_ASSOC);if($c){foreach($e as$f=>&$g){$this->map($g,$c);}}$this->result=$e;}else if($d===1){$this->stmt=$a;$this->complete=false;$this->result=array();}$this->affected=$a->rowCount();}}function close(){$this->complete=true;if($this->stmt){$this->stmt->closeCursor();$this->stmt=null;}}private function fetchNextRow(){$a=$this->stmt->fetch(\PDO::FETCH_ASSOC);if($a){if($this->outTypes){$this->map($a,$this->outTypes);}array_push($this->result,$a);return$a;}else{$this->close();return false;}}private function fetchAll(){while($this->fetchNextRow()){}}function map(&$a,&$b){foreach($b as$c=>$d){if(isset($a[$c])){switch($d){case 'int':$a[$c]=(int)$a[$c];break;case 'double':$a[$c]=(double)$a[$c];break;case 'string':$a[$c]=(string)$a[$c];break;case 'bool':$a[$c]=$a[$c]?true:false;break;case 'json':$a[$c]=json_decode($a[$c]);break;case 'object':$a[$c]=unserialize($a[$c]);break;}}}}function error(){return$this->errorData;}function getData($a=false){if(!$this->complete&&!$a)$this->fetchAll();return$this->result;}function rowCount(){return$this->affected;}function offsetSet($a,$b){}function offsetExists($a){return$this->offsetGet($a)===null?false:true;}function offsetUnset($a){}function offsetGet($a){if(is_int($a)){if(isset($this->result[$a])){return$this->result[$a];}else if(!$this->complete){while($this->fetchNextRow()){if(isset($this->result[$a]))return$this->result[$a];}}}return null;}function next(){if(isset($this->result[$this->ind])){return$this->result[$this->ind++];}else if(!$this->complete){$a=$this->fetchNextRow();$this->ind++;return$a;}else{return false;}}function rewind(){$this->ind=0;}function current(){return$this->result[$this->ind];}function key(){return$this->ind;}function valid(){return$this->offsetExists($this->ind);}}class Connector{public$db;public$log=array();public$dev=false;function __construct($a,$b,$c){$this->db=new \PDO($a,$b,$c);}function query($a,$b=null,$c=null,$d=0){$e=$this->db->prepare($a);if($b)$f=$e->execute($b);else$f=$e->execute();if($this->dev)array_push($this->log,array($a,$b));if($d!==3){return new Response($e,$f,$c,$d);}else{return$e;}}function _query($a,$b,$c,$d=null,$e=0){$f=$this->db->prepare($a);if($this->dev)array_push($this->log,array($a,$b,$c));foreach($b as$g=>&$h){$f->bindParam($g+1,$h[0],$h[1]);}$i=$f->execute();if(!isset($c[0])){return new Response($f,$i,$d,$e);}else{$j=array();array_push($j,new Response($f,$i,$d,0));foreach($c as$g=>$k){foreach($k as$l=>&$m){$b[$l][0]=$m;}$i=$f->execute();array_push($j,new Response($f,$i,$d,0));}return$j;}}function close(){$this->db=null;}}
// lib/parser.php
class Parser{static function getArg(&$a){preg_match('/^(?:\[(.{2})\])(.*)/',$a,$b);if(isset($b[1])){$a=$b[2];return$b[1];}return false;}static function isRaw(&$a){if($a[0]==='#'){$a=substr($a,1);return true;}return false;}static function isSpecial($a){return$a==='json'||$a==='object';}static function append(&$a,$b,$c,$d){if(is_array($b)&&$d[$c][2]<5){$e=count($b);for($f=1;$f<$e;$f++){if(!isset($a[$f-1]))$a[$f-1]=array();$a[$f-1][$c]=$b[$f];}}}static function stripArgs(&$a){preg_match('/(?:\[.{2}\]){0,3}([^\[]*)/',$a,$b);return$b[1];}static function append2(&$a,$b,$c,&$d,$e=false){$f=count($c);if($e){self::recurse($d,$c[0],$b,'',$d,0);}for($g=1;$g<$f;$g++){if(!isset($a[$g-1]))$a[$g-1]=array();self::recurse($a[$g-1],$c[$g],$b,'',$d,1);}}private static function recurse(&$a,$b,$c,$d,$e,$f){foreach($b as$g=>$h){$g=self::stripArgs($g);$i=$g.'#'.$d;if(isset($c[$i]))$j=$c[$i];else$j=$c[$g];if(is_array($h)&&!self::isSpecial($e[$j][2])){if(isset($h[0])){foreach($h as$k=>$l){$k+=$j;if($f&&isset($a[$k]))trigger_error('Key collision: '.$g,E_USER_WARNING);$a[$k]=self::value($e[$k][2],$l);if($f)$a[$k]=$a[$k][0];}}else{self::recurse($a,$h,$c,$d.'/'.$g,$e,$f);}}else{if($f&&isset($a[$j]))trigger_error('Key collision: '.$g,E_USER_WARNING);$a[$j]=self::value($e[$j][2],$h);if($f)$a[$j]=$a[$j][0];}}}static function quote($a){preg_match('/([a-zA-Z0-9_]*)\.?([a-zA-Z0-9_]*)?/',$a,$b);if($b[2]!==''){return '`'.$b[1].'`.`'.$b[2].'`';}else{return '`'.$b[1].'`';}}static function quoteArray(&$a){foreach($a as&$b){$b=self::quote($b);}}static function table($a){if(is_array($a)){$b='';foreach($a as$c=>$d){$e=self::getType($d);if($c!==0)$b.=', ';$b.='`'.$d.'`';if($f)$b.=' AS `'.$e.'`';}return$b;}else{return '`'.$a.'`';}}static function value($a,$b){if(!$a)$a=gettype($b);$c=\PDO::PARAM_STR;if($a==='integer'||$a==='int'){$c=\PDO::PARAM_INT;$b=(int)$b;}else if($a==='string'||$a==='str'||$a==='double'){$b=(string)$b;}else if($a==='boolean'||$a==='bool'){$c=\PDO::PARAM_BOOL;$b=$b?'1':'0';}else if($a==='null'||$a==='NULL'){$c=\PDO::PARAM_NULL;$b=null;}else if($a==='resource'||$a==='lob'){$c=\PDO::PARAM_LOB;}else if($a==='json'){$b=json_encode($b);}else if($a==='object'){$b=serialize($b);}else{trigger_error('Invalid type '.$a,E_USER_WARNING);}return array($b,$c,$a);}static function getType(&$a){preg_match('/([^\[]*)(?:\[([^\]]*)\])?/',$a,$b);$a=$b[1];return isset($b[2])?$b[2]:false;}static function rmComments($a){preg_match('/([^#]*)/',$a,$b);return$b[1];}static function conditions($a,&$b,&$c=false,&$d=0,$e=' AND ',$f=' = ',$g=''){$h=0;$i='';foreach($a as$j=>$k){if(is_int($j))$j=$k;preg_match('/^(?<r>\#)?(?:(?:\[(?<a>.{2})\])(?:(?:\[(?<b>.{2})\])(?:\[(?<c>.{2})\])?)?)?(?<out>.*)/',$j,$l);$m=($l['r']==='#');$n=$l['a'];$j=$l['out'];$o=$e;$p=$f;$q=$m?false:self::getType($j);$r=is_array($k)&&!self::isSpecial($q);$s=$r&&!isset($k[0]);if($n&&($n==='||'||$n==='&&')){$o=($n==='||')?' OR ':' AND ';$n=$l['b'];if($r&&$n&&($n==='||'||$n==='&&')){$e=$o;$o=($n==='||')?' OR ':' AND ';$n=$l['c'];}}$t=false;$u=false;if($n&&$n!=='=='){if($n==='!='||$n==='>='||$n==='<='){$p=' '.$n.' ';}else if($n==='>>'){$p=' > ';}else if($n==='<<'){$p=' < ';}else if($n==='~~'){$p=' LIKE ';}else if($n==='!~'){$p=' NOT LIKE ';}else if($n==='><'||$n==='<>'){$t=true;}else if($r&&$n==='MM'){$u=$l['c']?$l['c']:$l['b'];$v=array('NN'=>'IN NATURAL LANGUAGE MODE','NQ'=>'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION','BB'=>'IN BOOLEAN MODE','QQ'=>'WITH QUERY EXPANSION');$u=isset($v[$u])?' '.$v[$u]:'';}else{throw new \Exception('Invalid operator '.$n.' Available: ==,!=,>>,<<,>=,<=,~~,!~,<>,><');}}else if($s||$n==='==')$p=' = ';if(!$r)$e=$o;if($h!==0)$i.=$e;$w=self::rmComments($j);if(!$m)$w=self::quote($w);if($r){$i.='(';if($s){$i.=self::conditions($k,$b,$c,$d,$o,$p,$g.'/'.$j);}else{if($c!==false&&!$m){$c[$j]=$d;$c[$j.'#'.$g]=$d++;}if($u!==false){$x=$k['keyword'];unset($k['keyword']);self::quoteArray($k);$i.='MATCH('.implode($k,', ').') AGAINST (?'.$u.')';array_push($b,self::value($q,$x));}else if($t){$d+=2;$i.=$w.($n==='<>'?'NOT':'').' BETWEEN ';if($m){$i.=$k[0].' AND '.$k[1];}else{$i.='? AND ?';array_push($b,self::value($q,$k[0]));array_push($b,self::value($q,$k[1]));}}else{$l=isset($k[1]);$y=$l?count($k):$k[0];for($z=0;$z<$y;$z++){$aa=$l?$k[$z]:'';if($z!==0)$i.=$o;++$d;$i.=$w.$p;if($m){$i.=$aa;}else{$i.='?';array_push($b,self::value($q,$aa));}}}}$i.=')';}else{$i.=$w.$p;if($m){$i.=$k;}else{$i.='?';array_push($b,self::value($q,$k));if($c!==false){$c[$j]=$d;$c[$j.'#'.$g]=$d++;}}}++$h;}return$i;}static function JOIN($a,&$b,&$c,&$d){foreach($a as$e=>&$f){$g=self::isRaw($e);$h=self::getArg($e);switch($h){case '<<':$b.=' RIGHT';break;case '>>':$b.=' LEFT';break;case '<>':$b.=' FULL';break;case '>~':$b.=' LEFT OUTER';break;}$b.=' JOIN `'.$e.'` ON ';if($g){$b.=$f;}else{$b.=self::conditions($f,$c,$i,$d);}}}static function WHERE(&$a,$b,&$c,&$d,$e=0){$a.=' WHERE ';if(isset($b[0])){$f=array();$a.=self::conditions($b[0],$c,$f,$e);$g=isset($b[1][0]);self::append2($d,$f,$g?$b[1]:$b,$c,$g);}else{$a.=self::conditions($b,$c);}}static function columns($a,&$b,&$c){$d='';$e=$a[0][0];if($e==='D'||$e==='I'){if($a[0]==='DISTINCT'){$b.='DISTINCT ';array_splice($a,0,1);}else if(substr($a[0],0,11)==='INSERT INTO'){$b=$a[0].' '.$b;array_splice($a,0,1);}else if(substr($a[0],0,4)==='INTO'){$d=' '.$a[0].' ';array_splice($a,0,1);}}if(isset($a[0])){if($a[0]==='*'){array_splice($a,0,1);$b.='*';foreach($a as$f=>$g){$h=self::getType($g);$c[$g]=$h;}}else{foreach($a as$f=>$g){$i=self::getType($g);$j=false;if($i){$j=$i;$k=self::getType($g);if($k){$h=$k;}else{if($j==='json'||$j==='object'||$j==='int'||$j==='string'||$j==='bool'||$j==='double'){$h=$j;$j=false;}else$h=false;}if($h){if(!$c)$c=array();$c[$j?$j:$g]=$h;}}if($f!==0){$b.=', ';}$b.=self::quote($g);if($j)$b.=' AS `'.$j.'`';}}}else$b.='*';$b.=$d;}static function SELECT($a,$b,$c,$d,$e){$f='SELECT ';$g=$h=array();$i=null;$j=0;if(!isset($b[0])){$f.='*';}else{self::columns($b,$f,$i);}$f.=' FROM '.self::table($a);if($d){self::JOIN($d,$f,$g,$j);}if(!empty($c)){self::WHERE($f,$c,$g,$h,$j);}if($e){if(is_int($e)){$f.=' LIMIT '.$e;}else if(is_string($e)){$f.=' '.$e;}else if(is_array($e)){if(isset($e[0])){$f.=' LIMIT '.(int)$e[0].' OFFSET '.(int)$e[1];}else{if(isset($e['GROUP'])){$f.=' GROUP BY ';if(is_string($e['GROUP'])){$f.=self::quote($e['GROUP']);}else{self::quoteArray($e['GROUP']);$f.=implode(', ',$e['GROUP']);}if(isset($e['HAVING'])){$f.=' HAVING '.(is_string($e['HAVING'])?$e['HAVING']:self::conditions($e['HAVING'],$g,$k,$j));}}if(isset($e['ORDER'])){$f.=' ORDER BY '.self::quote($e['ORDER']);}if(isset($e['LIMIT'])){$f.=' LIMIT '.(int)$e['LIMIT'];}if(isset($e['OFFSET'])){$f.=' OFFSET '.(int)$e['OFFSET'];}}}}return array($f,$g,$h,$i);}static function INSERT($a,$b,$c){$d='INSERT INTO '.self::table($a).' (';$e=$f=$g=array();$h='';$i=0;$j=isset($b[0]);$k=isset($b[1][0]);$l=$j?$b[0]:$b;foreach($l as$m=>$n){if($k)$m=$n;$o=self::isRaw($m);if($i){$d.=', ';$h.=', ';}else$i=1;if(!$o)$p=self::getType($m);if($k)$n=$b[1][0][$m];$d.='`'.$m.'`';if($o){$h.=$n;}else{$h.='?';$q=!$j&&(!$p||!self::isSpecial($p))&&is_array($n);array_push($e,self::value($p,$q?$n[0]:$n));if($j){$g[$m]=array($n,$p);}else if($q){self::append($f,$n,$i++,$e);}}}$d.=') VALUES ('.$h.')';if($j){if($k)$b=$b[1];unset($b[0]);foreach($b as$r){$d.=', ('.$h.')';foreach($g as$m=>$n){array_push($e,self::value($n[1],isset($r[$m])?$r[$m]:$n[0]));}}}if($c)$d.=' '.$c;return array($d,$e,$f);}static function UPDATE($a,$b,$c){$d='UPDATE '.self::table($a).' SET ';$e=$f=$g=array();$h=$i=0;$j=isset($b[0]);$k=$j?$b[0]:$b;$l=isset($b[1][0]);foreach($k as$m=>$n){if($l)$m=$n;$o=self::isRaw($m);if($i){$d.=', ';}else$i=1;if($o){$d.='`'.$m.'` = '.$n;}else{$p=self::getArg($m);$q=self::getType($m);if($l)$n=$b[1][0][$m];$d.='`'.$m.'` = ';if($p){$d.='`'.$m.'` ';switch($p){case '+=':$d.='+ ?';break;case '-=':$d.='- ?';break;case '/=':$d.='/ ?';break;case '*=':$d.='* ?';break;}}else$d.='?';$r=(!$q||!self::isSpecial($q))&&is_array($n);array_push($e,self::value($q,$r?$n[0]:$n));if($j){$g[$m]=$h++;}else if($r){self::append($f,$n,$h++,$e);}}}if($j)self::append2($f,$g,$l?$b[1]:$b,$e);if(!empty($c))self::WHERE($d,$c,$e,$f,$h);return array($d,$e,$f);}static function DELETE($a,$b){$c='DELETE FROM '.self::table($a);$d=$e=array();if(!empty($b)){self::WHERE($c,$b,$d,$e);}return array($c,$d,$e);}}
// index.php
class SuperSQL{public$con;function __construct($a,$b,$c){$this->con=new Connector($a,$b,$c);}function SELECT($a,$b=array(),$c=array(),$d=null,$e=false){if((is_int($d)||is_string($d)||isset($d[0]))&&!$e){$e=$d;$d=null;}$f=Parser::SELECT($a,$b,$c,$d,$e);return$this->con->_query($f[0],$f[1],$f[2],$f[3],1);}function INSERT($a,$b,$c=null){$d=Parser::INSERT($a,$b,$c);return$this->con->_query($d[0],$d[1],$d[2]);}function UPDATE($a,$b,$c=array()){$d=Parser::UPDATE($a,$b,$c);return$this->con->_query($d[0],$d[1],$d[2]);}function DELETE($a,$b=array()){$c=Parser::DELETE($a,$b);return$this->con->_query($c[0],$c[1],$c[2]);}function query($a,$b=null,$c=null,$d=0){return$this->con->query($a,$b,$c,$d);}function close(){$this->con->close();}function dev(){$this->con->dev=true;}function getLog(){return$this->con->log;}function transact($a){$this->con->db->beginTransaction();try{$b=$a($this);}catch(\Exception$c){$this->con->db->rollBack();return false;}if($b===false)$this->con->db->rollBack();else$this->con->db->commit();return$b;}}
?>