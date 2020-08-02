<?php $__env->startSection('title', 'New admin'); ?>


<?php $__env->startSection('layout_content'); ?>

    <div class="row">
        <div class="col-md-4 col-md-offset-4">
            <div class="login-panel panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Change password <?php echo $detail; ?></h3>
                </div>
                <div class="panel-body">
                    <form role="form" method="post" action="index.php?path=dvups_admin/changepassword" >
                        <fieldset>
                            <div class="form-group">
                                <label>Old Password</label>
                                <input class="form-control" placeholder="Old Password" name="oldpwd" type="password" autocomplete="false" value=""  autofocus />
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input class="form-control" placeholder="New Password" name="newpwd" type="password" autocomplete="false"  value="" />
                            </div>

                            <button type="submit" class="btn btn-lg btn-success btn-block">Changer</button>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </div>


<?php $__env->stopSection(); ?>
<?php echo $__env->make('layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\devupstuto\src\devups\ModuleAdmin\Ressource\views/dvups_admin/changepwd.blade.php ENDPATH**/ ?>