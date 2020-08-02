<?php $__env->startSection('title', 'Page Title'); ?>

<?php function style(){ ?>

<?php foreach (dclass\devups\Controller\Controller::$cssfiles as $cssfile){ ?>
<link href="<?= $cssfile ?>" rel="stylesheet">
<?php } ?>

<?php } ?>

<?php $__env->startSection('content'); ?>
 
            <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="pe-7s-car icon-gradient bg-mean-fruit">
                    </i>
                </div>
                <div><?php echo e($moduledata->getName()); ?>

                    <div class="page-title-subheading">Some text</div>
                </div>
            </div>
            <div class="page-title-actions">

            </div>
        </div>
    </div>
    <ul class="nav nav-justified">
        <li class="nav-item">
            <a class="nav-link active" href="<?= path('src/' . strtolower($moduledata->getProject()) . '/' . $moduledata->getName() . '') ?>">
                <i class="metismenu-icon"></i> <span>Dashboard</span>
            </a>
        </li>
        <?php $__currentLoopData = $moduledata->dvups_entity; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="nav-item">
                <a class="nav-link active" href="<?= path('src/' . strtolower($moduledata->getProject()) . '/' . $moduledata->getName() . '/' . $entity->getUrl() . '/index') ?>">
                    <i class="metismenu-icon"></i> <span><?= $entity->getLabel() ?></span>
                </a>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    <hr>

    <?php echo $__env->yieldContent('layout_content'); ?>


        <?php $__env->stopSection(); ?>
        
<?php function script(){ ?>

<script src="<?= CLASSJS ?>devups.js"></script>
<script src="<?= CLASSJS ?>model.js"></script>
<script src="<?= CLASSJS ?>ddatatable.js"></script>
<?php foreach (dclass\devups\Controller\Controller::$jsfiles as $jsfile){ ?>
<script src="<?= $jsfile ?>"></script>
<?php } ?>

<?php } ?>

	
<?php echo $__env->make('layout.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\devupstuto\src\devupstuto\ModuleStock\Ressource\views/layout.blade.php ENDPATH**/ ?>