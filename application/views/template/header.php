<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="<?=base_url()?>/asset/assets/img/apple-icon.png">
  <title>Project</title>
  <!--     Fonts and icons     -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">	<!-- Nucleo Icons -->
  <link href="<?=base_url()?>/asset/assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="<?=base_url()?>/asset/assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <!--<script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>-->
  <script src="<?=base_url()?>/asset/assets/fjs/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <!-- CSS Files -->
  <link id="pagestyle" href="<?=base_url()?>/asset/assets/css/material-dashboard.css?v=3.0.4" rel="stylesheet" />
   <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <link rel="stylesheet" href="<?=base_url()?>/asset/build/toastr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
</head>
  
  <style>
  
  
@media print {
  body.bg-gray-200 {
    background-color: transparent !important;
  }
}
  
.containers {
    display: flex;
    flex-direction: row;
}
.item {
    display: flex;
    flex-shrink: 0;
}
.divider {
    display: flex;
    flex: 1;
}

.opacity-7 {
    opacity: 1!important;
}

.btn-group-sm>.btn i, .btn.btn-sm i {
    font-size: 13px;
    margin-right: 5px;
}
.navbar-vertical.navbar-expand-xs .navbar-nav .nav-link {
	margin:0;
}
.navbar-vertical .navbar-nav .nav-link>i {
	    min-width: 20PX;
    font-size: 20PX;
}



.modal {
z-index: 10000;
 }
 
.form-control{ padding-left: 10px;padding-right: 10px; margin-bottom:10px;	}
 

.table tbody tr:last-child td {
    text-wrap: balance;
}
 </style>
 
 
 
 
 
 
 
</head>



<style>
.dark-version .form-control, body.dark-version {
    color: hsl(0deg 0% 100% / 80%)!important;
}





.dark-version  .modal-content {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    pointer-events: auto;
    background-color: #344767;
    background-clip: padding-box;
    border: 1px solid rgba(0,0,0,.2);
    border-radius: 0.5rem;
    outline: 0;
}



.dark-version .tablenew th, td {

    color: #344767;

}


.dark-version .text-dark {
    color: #ffffff!important;
}

.dark-version .bg-white .text-dark {
    color: #344767!important;
}


.dark-version .fixed-plugin .fixed-plugin-button {
background: #344767; }



.dark-version  .blur {
    box-shadow: inset 0 0 2px #fefefed1;
    -webkit-backdrop-filter: saturate(200%) blur(30px);
    backdrop-filter: saturate(200%) blur(30px);
    background-color: #344767!important;
}


.dark-version .navbar .nav-link, .navbar .navbar-brand {
    color: #ffffff;
    
}


/* .dark-version .tablenew th, td {
    color: #acacac;
}
 */


.white-version .tablenew th, td {
    color: #202940;
}

.dark-version .select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #1a2035!important;
    color: white;
}

.dark-version .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #fff;
    line-height: 28px;
}

.dark-version .select2-container--default .select2-selection--single {
    background-color: #1a2035;
    border: 1px solid #aaa;
    border-radius: 4px;
}
.dark-version .select2-container--open .select2-dropdown--below {

    background-color: #344767;
}

.dark-version textarea {
    resize: vertical;
    background-color: #1a2035;
	color: #ffffff;
}






.dark-version .card-footer {
    padding: 0.5rem 1rem;
    background-color: #344767;
    border-top: 0 solid rgba(0,0,0,.125);
}


</style>


<body class="g-sidenav-show  bg-gray-200 white-version" >
  <aside id="printPageButton" class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3   bg-gradient-dark" id="sidenav-main">
    <div class="sidenav-header">
      <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
      <a class="navbar-brand m-2" href="<?=base_url()?>/home" >
        <span class="ms-1 font-weight-bold text-white"></span>
      </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">
    <div class="collapse navbar-collapse  w-auto h-auto " id="sidenav-collapse-main">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link text-white active bg-gradient-primary" onclick="viewmenu(event)" style="cursor: pointer;">
           
          </a>
        </li>
		
		<li class="nav-item">
          <a class="nav-link text-white info" href="<?=base_url()?>home/index">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">dashboard</i>
            </div>
            <span class="nav-link-text ms-1">Dashboard</span>
          </a>
        </li>
				<li class="nav-item">
					<a data-bs-toggle="collapse" href="#componentsproj" class="nav-link " aria-controls="componentsExamples" role="button" aria-expanded="false">
					<i class="material-icons-round ">people</i>
					<span class="nav-link-text ms-2 ps-1"> Timesheet</span>
					</a>
					<div class="collapse " id="componentsproj">
					<ul class="nav ">
					<li class="nav-item ">
						<a class="nav-link  " href="<?=base_url()?>equipmenttimesheets" >
						<span class="sidenav-mini-icon"> E</span>
						<span class="sidenav-normal  ms-2  ps-1">Equipment</span>
						</a>
						</li>
						<li class="nav-item">
						<a class="nav-link " href="<?=base_url()?>manpowertimesheets" >
						<span class="sidenav-mini-icon"> M</span>
						<span class="sidenav-normal  ms-2  ps-1">Manpower</span>
						</a>
						</li>
					</ul>
					</div>
					</li>
		
      </ul>
    </div>
   
  </aside>
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
    
    

<style>
#MyTable tr:hover { 
   background-color: #fcbad1;
}

.dark-version #MyTable tr:hover { 
   background-color: #344767;
}
</style>
