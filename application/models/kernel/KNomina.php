<?php 
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * MamonSoft 
 *
 * Calculos
 *
 * @package pace\application\modules\panel\model\beneficiario
 * @subpackage utilidad
 * @author Carlos Peña
 * @copyright Derechos Reservados (c) 2015 - 2016, MamonSoft C.A.
 * @link http://www.mamonsoft.com.ve
 * @since version 1.0
 */

class KNomina extends CI_Model{
  
  var $ID = '';

  var $Nombre = '';
  var $Descripcion = '';
  var $Monto = 0.00;
  var $Asignacion = 0.00;
  var $Deduccion = 0.00;
  var $Cantidad = 0;
  var $Tipo = 'SD'; //RCP | FCP | I | GRACIA
  var $Estatus = 0;
  
  var $fecha = '';
  /**
  * Iniciando la clase, Cargando Elementos Pace
  *
  * @access public
  * @return void
  */
  public function __construct(){
    parent::__construct();  
    if(!isset($this->DBSpace)) $this->load->model('comun/DBSpace');
  }

  public function Cargar($info = ""){
    $oid = 0;
    $p = json_encode($info["Concepto"]);
    $sConsulta = "INSERT INTO space.nomina ( nomb,obse,fech,desd,hast,tipo,mont,asig, dedu, cant, esta, info, url, mes ) VALUES (
    '" . $this->Nombre . "','" . $info["nombre"] . "',Now(),'" . $info["fechainicio"] . "','" . $info["fechafin"] . 
    "','" .  $info["tipo"] . 
    "'," . $this->Monto . "," . $this->Asignacion . "," . $this->Deduccion . 
    "," . $this->Cantidad  . "," . $this->Estatus  . ",'"  . $p . "','"  . base_url() . "','"  . $info['mes'] . "') RETURNING oid";

    $obj = $this->DBSpace->consultar($sConsulta);
    foreach ($obj->rs as $clv => $val) {
        $oid = $val->oid;
    }
    $this->ID = $oid;
  }

  public function Actualizar(){
    $sConsulta = "UPDATE space.nomina SET nomb = '" .  $this->Nombre . "', esta = " . $this->Estatus . ", tipo='" . $this->Tipo .  
    "', mont = " . round($this->Monto,2) . ", asig =" . round($this->Asignacion,2) . 
    ", dedu=" . round($this->Deduccion,2) . ", cant=" . $this->Cantidad  . " WHERE oid =" . $this->ID;
    $obj = $this->DBSpace->consultar($sConsulta);
    //echo "AQUI";
    return true;
  }

  public function Contar(){
    $sConsulta = "SELECT count(*) as cantidad FROM familiar fam 
      JOIN beneficiario bnf ON fam.titular=bnf.cedula 
      WHERE fam.estatus = 201 AND bnf.grado_id != 0";
    $obj = $this->DBSpace->consultar($sConsulta);
    $sobrevive = array();
    foreach($obj->rs as $c => $v ){
      $sobrevive['act'] = $v->cantidad;
    }

    //
    $sConsulta = "SELECT count(*) as cantidad FROM familiar fam 
      JOIN beneficiario bnf ON fam.titular=bnf.cedula 
      WHERE fam.estatus != 201 AND bnf.grado_id != 0";
    $obj = $this->DBSpace->consultar($sConsulta);
    
    foreach($obj->rs as $c => $v ){
      $sobrevive['par'] = $v->cantidad;
    }



    $sConsulta = "SELECT situacion, count(situacion) AS cantidad FROM beneficiario WHERE
    grado_id != 0 AND status_id = 201 GROUP BY situacion";
    $obj = $this->DBSpace->consultar($sConsulta);
    $contar = array();
    foreach($obj->rs as $c => $v ){
      if($v->situacion != "FCP"){
        $contar[] = array(
                "situacion" => $v->situacion, 
                "cantidad" => $v->cantidad);
      }
      else{
        $contar[] = array(
          "situacion" => $v->situacion, 
          "cantidad" => $sobrevive['act']);
      }
    }

    $sConsulta = "SELECT situacion, count(situacion) AS cantidad FROM beneficiario WHERE
    grado_id != 0 AND status_id != 201 GROUP BY situacion";
    $obj = $this->DBSpace->consultar($sConsulta);
    $paralizado = array();
    foreach($obj->rs as $c => $v ){
      if($v->situacion != "FCP"){
        $paralizado[] = array(
                "situacion" => $v->situacion, 
                "cantidad" => $v->cantidad);
      }
      else{
        $paralizado[] = array(
         "situacion" => $v->situacion, 
          "cantidad" => $sobrevive['par']);
      }
    }

    
    return array('act' => $contar, 'par' => $paralizado);
  }

  public function Listar($mes = "", $id = "", $ano = ""){
    if ( $id < 4 ){
      $sConsulta = "SELECT * FROM space.nomina WHERE esta IN ( 1, 2, 3)";
    }else{
      $sConsulta = "SELECT * FROM space.nomina WHERE esta IN ( 4, 6, 10 )  AND mes = '" . $mes . "' AND desd BETWEEN '$ano-01-01' AND '$ano-12-31'";
    }
    //echo $sConsulta;
    $obj = $this->DBSpace->consultar($sConsulta);
    $lst = array();
    foreach($obj->rs as $c => $v ){
      $lst[] = $v;
    }
    return $lst;
  }

  public function ListarPagos(){
    
    $sConsulta = "select * FROM (
      select llav as firma, sum(mont) as monto, sum(asig) as asignacion, 
          sum(dedu) as deduccion, sum(cant) as cantidad, min(oid) as minimo from     
          space.nomina WHERE 
            llav != ''  GROUP BY llav ORDER BY minimo
      ) as tb JOIN space.nomina sp ON tb.minimo=sp.oid";
   
    $obj = $this->DBSpace->consultar($sConsulta);
    $lst = array();
    foreach($obj->rs as $c => $v ){
      $lst[] = $v;
    }
    return $lst;
  }

  public function ListarCuadreBanco($firma, $tabla){
    

    $sConsulta = "SELECT banc, bnc.nomb, tp, cant, neto FROM (
      SELECT  pg.banc, pg.tipo as tp, count(pg.banc) AS cant, 
      SUM(neto) AS neto FROM space.nomina nm JOIN space." . $tabla . " pg ON pg.nomi=nm.oid
      WHERE nm.llav='" . $firma . "'
      GROUP BY  pg.banc, pg.tipo
      ORDER BY pg.banc) AS mt
      LEFT JOIN space.banco bnc ON mt.banc=bnc.codi
      ORDER BY bnc.codi";
    
    //echo $sConsulta;

    $obj = $this->DBSpace->consultar($sConsulta);
    $lst = array();
    foreach($obj->rs as $c => $v ){

      $lst[$v->banc][] = array(
        'nomb' => $v->nomb,
        'cant' => $v->cant,
        'tipo' => $v->tp,
        'neto' => $v->neto
      );
    }
    return $lst;
  }

  public function Procesar(){
    $sConsulta = "UPDATE space.nomina SET esta = " . $this->Estatus  . " WHERE oid =" . $this->ID;
    $obj = $this->DBSpace->consultar($sConsulta);
    return true;
  }

  public function RegistrarDetalle( $oidn, $presupuesto ){
    $i = 0;
    $coma = '';
    $insert = 'INSERT INTO space.nomina_detalle ( oidn, part, estr, conc,  fech, tipo, mont, codi, cuenta  ) VALUES ';
    foreach ($presupuesto as $c => $v) {     
      if ($v['tp'] != 97 ) {
        $i++;
        $coma = $i > 1?",":"";      
        $insert .= $coma . "(" . $oidn . ",'" . $v['part'] . "','" . $v['estr'] . "','" . $v['abv'] . 
        "',Now()," . $v['tp'] . "," . round($v['mnt'],2) . ",'" . $v['codi'] . "','" . $v['cuen'] . "')";
      }

    }
    //print_r($insert);
    $obj = $this->DBSpace->consultar($insert);
    return true;
  }


  public function VerDetalles(){
    $sConsulta = "SELECT * FROM space.nomina_detalle WHERE oidn=" . $this->ID;
    $obj = $this->DBSpace->consultar($sConsulta);
    $lst = array();
    foreach($obj->rs as $c => $v ){
      $lst[] = $v;
    }
    return $lst;
  }



  public function ListarPagosDetalles($post){
    if ( $post['codigo'] == "0000" ) {
      $sqlMas = "' AND banc='" . $post['codigo'] . "'";
    }else{
      $sqlMas = "' AND banc='" . $post['codigo'] . "' AND p.tipo='" . $post['tipo'] . "'";
    }
    $sConsulta = "select p.nomb AS nombre, * from space.pagos p JOIN space.nomina n ON 
    p.nomi=n.oid WHERE n.llav='" . $post['llave'] . 
    $sqlMas;
   
    $obj = $this->DBSpace->consultar($sConsulta);
    $lst = array();
    foreach($obj->rs as $c => $v ){
      $lst[] = $v;
    }
    return $lst;
  }

  public function ListarConceptos(){
    $sConsulta = "SELECT codigo, descripcion FROM space.conceptos";
   
    $obj = $this->DBSpace->consultar($sConsulta);
    $lst = array();
    foreach($obj->rs as $c => $v ){
      $lst[] = $v;
    }
    return $lst;
  }

  public function ListarNominaDetalle( $llav = ''){
    //85cb4d8068fd39e9fdba111d1c168ca3
    $sConsulta = "SELECT * FROM space.nomina_detalle AS ndt JOIN (select oid,tipo from space.nomina
    where llav = '" . $llav ."') as nomi ON ndt.oidn=nomi.oid";
    //echo $sConsulta;
    $obj = $this->DBSpace->consultar($sConsulta);
    $lst = array();
    foreach($obj->rs as $c => $v ){
      $lst[] = $v;
    }
    return $lst;

  }

  public function VerPagosIndividual($llav = '', $cedula = ''){
    

    $sConsulta = "SELECT *, pag.oid AS oidpago FROM space.nomina nom 
      JOIN space.pagos pag ON pag.nomi=nom.oid
      WHERE nom.llav = '" . $llav ."' AND ( pag.cedu='" . $cedula ."' OR pag.cfam='" . $cedula ."' OR pag.caut='" . $cedula ."' )";
    //echo $sConsulta;
    $obj = $this->DBSpace->consultar($sConsulta);
    $lst = array();
    foreach($obj->rs as $c => $v ){
      $lst[] = $v;
    }
    return $lst;
  }



   /**
   * Cargar las nóminas generadas en un año lectivo hasta la fecha
   */
  public function CargarMesesActivo($arr = array()){
    $desde = $arr['ano'] . '-01-01';
    $hasta = $arr['ano'] . '-12-31';
    $s = "SELECT desd, mes FROM (
            SELECT mes, desd, tipo, obse from space.nomina 
              WHERE desd BETWEEN '" . $desde . "' and '" . $hasta . "' AND llav != '' AND tipo = '" . $arr['tipo'] . "'
              AND obse NOT IN('DIFERENCIA DE SUELDO','DIFERENCIA DE BONO', 'DIFERENCIA DE CESTA TICKET', 'DIFERENCIA DE RETRIBUCION ESPECIAL')
            ORDER BY desd ) AS A  
          GROUP BY desd, mes ORDER BY desd";


    $obj = $this->DBSpace->consultar($s);
    return $obj;
  }


  /**
   * Cargar las nóminas generadas en un mes par los pagos de retroactivos
   */
  public function CargarMesDetalle($arr = array()){
    $desde = $arr['ano'] . '-01-01';
    $hasta = $arr['ano'] . '-12-31';

    $s = "SELECT * FROM (
      SELECT obse,esta, info, fech, cant, asig, dedu, mont, llav, oid FROM (
        SELECT * from space.nomina 
          WHERE desd BETWEEN '" . $desde . "' and '" . $hasta . "' AND llav != '' AND tipo = '" . $arr['tipo'] . "'
          AND obse NOT IN('DIFERENCIA DE SUELDO','DIFERENCIA DE BONO', 'DIFERENCIA DE CESTA TICKET', 'DIFERENCIA DE RETRIBUCION ESPECIAL')
          ORDER BY desd ) AS A WHERE mes='" . $arr['mes'] . "' 	
    ) AS cob 
      LEFT JOIN space.pagos AS pag ON cob.oid=pag.nomi AND pag.cedu='" . $arr['cedula'] . "'";

    $obj = $this->DBSpace->consultar($s);
    return $obj;
  }

   
  /**
   * Listar las nóminas para los pagos de retroactivos
   */
  public function PagarRetroactivos($arr = array()){
    $desde = $arr['ano'] . '-01-01';
    $hasta = $arr['ano'] . '-12-31';

    $s = 'SELECT * FROM space.nomina WHERE mes = ' . $arr['mes'] . ' AND desd BETWEEN ' . $desde . ' AND ' . $hasta;

    $obj = $this->DBSpace->consultar($s);

    return $obj;
  }

  public function ConfirmacionPagosPorCedula($arr = array()){

  }

}
