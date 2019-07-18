<?php

/**
*
*/
class KRechazos extends CI_Model{
    var $codigo = '';
    var $cuenta = '';
    var $tipo = '';
    var $banco = '';

	function __construct(){
		parent::__construct();
		if(!isset($this->DBSpace)) $this->load->model('comun/DBSpace');
    }

    public function Agregar($arr){

        $codigo = $arr['codigo'];
        $banco = $arr['banco'];
        $cuenta = $arr['cuenta'];
        $tipo = $arr['tipo'];

        $sConsulta = "INSERT INTO space.rechazos (oid,
           nomi,did, cedu,cfam,pare,caut, naut, nomb, calc,
           fech,banc, nume, tipo, situ,esta,usua,neto,
           base, grad,nive) SELECT * FROM space.pagos WHERE oid=" . $codigo;

        //echo $sConsulta;
        $obj = $this->DBSpace->consultar($sConsulta);

        $sConsulta = "UPDATE space.rechazos SET banc = '$banco', tipo='$tipo', nume='$cuenta' WHERE oid = $codigo AND nive=0";

        $obj = $this->DBSpace->consultar($sConsulta);
      
        return true;
    }
    public function Listar( $hash ){
        $sConsulta = "SELECT * FROM space.nomina nm 
        JOIN space.rechazos pg ON pg.nomi=nm.oid
        WHERE nm.llav='$hash'";
        
        $obj = $this->DBSpace->consultar($sConsulta);
        $lst = array();
        foreach($obj->rs as $c => $v ){
          $lst[] = $v;
        }
        return $lst;
    }
	

    public function Eliminar( $oid ){
        $sConsulta = "DELETE FROM space.rechazos WHERE oid='$oid'";
        
        $obj = $this->DBSpace->consultar($sConsulta);
        $lst = array();
     
        return $lst;
    }
	

}