<?php $__env->startSection('title', 'List'); ?>

<?php $__env->startSection('layout_content'); ?>

    <div class="row">
        <div class="col-lg-12 col-md-12  stretch-card">
            <div class="card">
                <div class="card-header-tab card-header">
                    <div class="card-header-title">
                        <i class="header-icon lnr-rocket icon-gradient bg-tempting-azure"> </i>
                        <?php echo e($title); ?>

                    </div>
                    <div class="btn-actions-pane-right">
                        <div class="nav">

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?= $datatablehtml; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="<?php echo e(strtolower($entity)); ?>box" class="swal2-container swal2-fade swal2-shown" style="display:none; overflow-y: auto;">
        <div role="dialog" aria-labelledby="swal2-title" aria-describedby="swal2-content" class="swal2-modal swal2-show dv_modal" tabindex="1"
             style="">
            <div class="main-card mb-3 card  box-container">
                <div class="card-header">.

                    <button onclick="model._dismissmodal()" type="button" class="swal2-close" aria-label="Close this dialog" style="display: block;">×</button>
                </div>
                <div class="card-body"></div>
            </div>

        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php function modalview($entity){ ?>

<div class="modal fade" id="<?php echo e(strtolower($entity)); ?>modal" tabindex="-1" role="dialog" aria-labelledby="modallabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="title" id="modallabel">Modal Label</h3>
            </div>
            <div class="modal-body panel generalinformation"></div>
            <div class="modal-footer">
                <button onclick="model._dismissmodal()" data-dismiss="modal" aria-label="Close" type="button" class="btn btn-danger">Close</button>
            </div>
        </div>
    </div>
</div>
<?php } ?>


<?php echo $__env->make('layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\devupstuto\admin\views/default/index.blade.php ENDPATH**/ ?>