<?php

require_once __DIR__ . '/../notification_helper.php';
$unread = NotificationHelper::countUnread($authUser['id']);

?>

<div class="topbar">
    <h5 class="page-title">
        <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?>
    </h5>

    <div class="d-flex align-items-center gap-3">

        <div class="notif-bell position-relative"
             onclick="window.location='<?= BASE_URL ?>/user/notifications.php'"
             title="Notifications">
            <i class="fa fa-bell fs-5 text-secondary"></i>
            <span class="notif-badge"
                  id="topbarNotifBadge"
                  style="display:<?= $unread > 0 ? 'flex' : 'none' ?>">
                <?= $unread > 9 ? '9+' : $unread ?>
            </span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center"
                 style="width:36px;height:36px;font-size:.9rem;color:#fff;font-weight:600">
                <?= strtoupper(substr($authUser['name'],0,1)) ?>
            </div>
            <div class="d-none d-md-block">
                <div style="font-size:.88rem;font-weight:600;line-height:1.2">
                    <?= htmlspecialchars($authUser['name']) ?>
                </div>
                <div style="font-size:.75rem;color:#888">
                    <?= ucfirst($authUser['role']) ?>
                </div>
            </div>
        </div>

        <a href="<?= BASE_URL ?>/auth/logout.php"
           class="btn btn-sm btn-outline-danger"
           title="Logout"
           onclick="this.innerHTML='<i class=\'fa fa-spinner fa-spin\'></i>'; return true;">
            <i class="fa fa-right-from-bracket"></i>
        </a>
    </div>
</div>