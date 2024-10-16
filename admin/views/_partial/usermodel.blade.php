<!--<img src="../web/images/avatar.png" class="img-responsive" alt="">-->
<div class="desc flex justify-space-between">

    <div>
        <?= $user->id; ?>,
        <?= $user->username; ?><br>
        <?= $user->email; ?>,<br>
        +<?= $user->_country->phonecode; ?> <?= $user->phonenumber; ?><br>
         <?= $user->_country->name; ?> / $user->townname; ?><br>
    </div>
    <a href="{{User::classview('user/detail?id=').$user->id}}" target="_blank" class="btn  btn-outline-info"><i
                class="fa fa-eye"></i> Voir le profile</a>
</div>
<div class="clearfix"></div>
