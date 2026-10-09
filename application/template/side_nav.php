<nav class="sidenav navbar navbar-vertical  fixed-left  navbar-expand-xs navbar-light" id="sidenav-main">
        <div class="scrollbar-inner">

            <div class="sidenav-header  d-flex  align-items-center">
                <a class="navbar-brand" href="javascript:void(0)">
                <img src="<?= base_url('assets/img/ethicfin-logo-white.png') ?>" class="navbar-brand-img" alt="...">
                </a>
                <div class=" ml-auto ">

                    <div class="sidenav-toggler d-none d-xl-block" data-action="sidenav-unpin" data-target="#sidenav-main">
                        <div class="sidenav-toggler-inner">
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="navbar-inner">

                <div class="collapse navbar-collapse" id="sidenav-collapse-main">

                    <ul class="navbar-nav">
                        
                        <?= $this->include('template/nav_shahana'); ?>
                        
                    </ul>

                    
                </div>
            </div>
        </div>
    </nav>