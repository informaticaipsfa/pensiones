<!DOCTYPE html>
<html>
  <?php $this->load->view('inc/cabecera.php');?>
  
    <!-- Site wrapper -->
    <div class="wrapper">

      <?php $this->load->view('inc/top.php');?>

      
      <!-- =============================================== -->

      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h1>
            Panel de PACE
            <small></small>
          </h1>
          <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Principal</a></li>
            
          </ol><br>

          <div class="row">
            <div class="col-lg-3 col-xs-6">
              <!-- small box -->
              <div class="small-box bg-aqua">
                <div class="inner">
                  <h2>Beneficiario</h2>

                  <p>Consultar</p>
                </div>
                <div class="icon">
                  <i class="ion ion-person-stalker"></i>
                </div>
                <a href="<?php echo base_url()?>index.php/panel/Panel/beneficiario" class="small-box-footer">Continuar <i class="fa fa-arrow-circle-right"></i></a>
              </div>
            </div> 

            <div class="col-lg-3 col-xs-6">
              <!-- small box -->
              <div class="small-box bg-green">
                <div class="inner">
                  <h2>Finiquito</h2>

                  <p>Consultar</p>
                </div>
                <div class="icon">
                  <i class="ion ion-card"></i>
                </div>
                <a href="<?php echo base_url()?>index.php/panel/Panel/finiquitos" class="small-box-footer">Continuar <i class="fa fa-arrow-circle-right"></i></a>
              </div>
            </div>

            <div class="col-lg-3 col-xs-6">
              <!-- small box -->
              <div class="small-box bg-yellow">
                <div class="inner">
                  <h2>Anticipos</h2>

                  <p>Consultar</p>
                </div>
                <div class="icon">
                  <i class="ion ion-calculator"></i>
                </div>
                <a href="<?php echo base_url()?>index.php/panel/Panel/anticipo" class="small-box-footer">Continuar <i class="fa fa-arrow-circle-right"></i></a>
              </div>
            </div>

            <div class="col-lg-3 col-xs-6">
              <!-- small box -->
              <div class="small-box bg-red">
                <div class="inner">
                  <h2>Reportes</h2>

                  <p>Generar</p>
                </div>
                <div class="icon">
                  <i class="ion ion-printer"></i>
                </div>
                <a href="<?php echo base_url()?>index.php/panel/Panel/reporte" class="small-box-footer">Continuar <i class="fa fa-arrow-circle-right"></i></a>
              </div>
            </div>

          </div>
        </section>

        <!-- Main content -->

      </div><!-- /.content-wrapper -->

      <footer class="main-footer">
        <div class="pull-right hidden-xs">
          <b>Version</b> 2.6.7
        </div>
        <strong>Copyright &copy; 2015-2016 Instituto de Previsión Social.</strong> Todos los derechos.
      </footer>

    </div><!-- ./wrapper -->

    <?php $this->load->view('inc/pie.php');?>
  </body>
</html>