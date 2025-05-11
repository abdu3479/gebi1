<?php
// sms_dashboard.php
require_once '../config/database.php';
require_once '../classes/SMSService.php';
require_once '../includes/auth_check.php'; // Your authentication check

$db = new Database();
$smsService = new SMSService($db);

// Get stats
$stats = $smsService->getSMSStats();
$pending = $smsService->getPendingNotifications();
$failed = $smsService->getFailedNotifications();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Dashboard - Property Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <!-- Header and Sidebar (from your existing code) -->
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>SMS Notifications</h1>
                    </div>
                    <div class="col-sm-6">
                        <button class="btn btn-primary float-right" data-toggle="modal" data-target="#sendSmsModal">
                            <i class="fas fa-plus"></i> Send New SMS
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?= $stats['total_sent']; ?></h3>
                                <p>Total Sent</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?= $stats['delivered']; ?></h3>
                                <p>Delivered</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?= $stats['pending']; ?></h3>
                                <p>Pending</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3><?= $stats['failed']; ?></h3>
                                <p>Failed</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Notifications -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pending Notifications</h3>
                        <div class="card-tools">
                            <button class="btn btn-sm btn-primary" onclick="processPending()">
                                <i class="fas fa-sync"></i> Process Now
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Recipient</th>
                                    <th>Message</th>
                                    <th>Scheduled</th>
                                    <th>Attempts</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending as $item): ?>
                                <tr>
                                    <td><?= $item['id']; ?></td>
                                    <td><?= htmlspecialchars($item['phone_number']); ?></td>
                                    <td><?= htmlspecialchars(substr($item['message'], 0, 50)); ?>...</td>
                                    <td><?= date('M j, Y H:i', strtotime($item['send_date'])); ?></td>
                                    <td><?= $item['attempts']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewDetails(<?= $item['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="cancelNotification(<?= $item['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Failed Notifications -->
                <div class="card mt-4">
                    <div class="card-header bg-danger">
                        <h3 class="card-title">Failed Notifications</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Recipient</th>
                                    <th>Error</th>
                                    <th>Last Attempt</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($failed as $item): ?>
                                <tr>
                                    <td><?= $item['id']; ?></td>
                                    <td><?= htmlspecialchars($item['phone_number']); ?></td>
                                    <td><?= htmlspecialchars(substr($item['error_message'], 0, 50)); ?>...</td>
                                    <td><?= date('M j, Y H:i', strtotime($item['updated_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="retryNotification(<?= $item['id']; ?>)">
                                            <i class="fas fa-redo"></i> Retry
                                        </button>
                                        <button class="btn btn-sm btn-info" onclick="viewDetails(<?= $item['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Send SMS Modal -->
    <div class="modal fade" id="sendSmsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send New SMS</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="sendSmsForm" onsubmit="return sendNewSms()">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Recipient Type</label>
                            <select class="form-control" id="recipientType" onchange="updateRecipients()">
                                <option value="owner">Owner</option>
                                <option value="tenant">Tenant</option>
                                <option value="custom">Custom Number</option>
                                <option value="group">Group</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="recipientSelectGroup">
                            <label>Select Recipient</label>
                            <select class="form-control" id="recipientSelect">
                                <!-- Populated via AJAX -->
                            </select>
                        </div>
                        
                        <div class="form-group d-none" id="customNumberGroup">
                            <label>Phone Number</label>
                            <input type="text" class="form-control" id="customNumber" placeholder="+251...">
                        </div>
                        
                        <div class="form-group">
                            <label>Message Template</label>
                            <select class="form-control" id="smsTemplate" onchange="loadTemplate()">
                                <option value="">Custom Message</option>
                                <?php foreach ($smsService->getTemplates() as $template): ?>
                                    <option value="<?= $template['id']; ?>"><?= htmlspecialchars($template['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Message Content</label>
                            <textarea class="form-control" id="smsContent" rows="5" maxlength="612"></textarea>
                            <small class="text-muted"><span id="charCount">0</span>/612 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Schedule</label>
                            <select class="form-control" id="smsSchedule">
                                <option value="now">Send Now</option>
                                <option value="later">Schedule for Later</option>
                            </select>
                        </div>
                        
                        <div class="form-group d-none" id="scheduleDateGroup">
                            <label>Send Date/Time</label>
                            <input type="datetime-local" class="form-control" id="scheduleDate">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send SMS
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Notification Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Notification Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>

<script>
// Character counter
$('#smsContent').on('input', function() {
    $('#charCount').text($(this).val().length);
});

// Schedule toggle
$('#smsSchedule').change(function() {
    if ($(this).val() === 'later') {
        $('#scheduleDateGroup').removeClass('d-none');
        // Set default to next hour
        const now = new Date();
        now.setHours(now.getHours() + 1);
        $('#scheduleDate').val(now.toISOString().slice(0, 16));
    } else {
        $('#scheduleDateGroup').addClass('d-none');
    }
});

// Recipient type toggle
function updateRecipients() {
    const type = $('#recipientType').val();
    
    if (type === 'custom') {
        $('#recipientSelectGroup').addClass('d-none');
        $('#customNumberGroup').removeClass('d-none');
    } else {
        $('#recipientSelectGroup').removeClass('d-none');
        $('#customNumberGroup').addClass('d-none');
        
        // Load recipients via AJAX
        $.ajax({
            url: 'ajax/get_recipients.php',
            type: 'POST',
            data: { type: type },
            success: function(response) {
                $('#recipientSelect').html(response);
            }
        });
    }
}

// Load template
function loadTemplate() {
    const templateId = $('#smsTemplate').val();
    if (!templateId) return;
    
    $.ajax({
        url: 'ajax/get_template.php',
        type: 'POST',
        data: { id: templateId },
        success: function(response) {
            $('#smsContent').val(response.content);
            $('#charCount').text(response.content.length);
        }
    });
}

// View notification details
function viewDetails(id) {
    $.ajax({
        url: 'ajax/get_notification_details.php',
        type: 'POST',
        data: { id: id },
        success: function(response) {
            $('#detailsContent').html(response);
            $('#detailsModal').modal('show');
        }
    });
}

// Process pending notifications
function processPending() {
    if (!confirm('Process all pending notifications now?')) return;
    
    $.ajax({
        url: 'ajax/process_pending.php',
        type: 'POST',
        beforeSend: function() {
            // Show loading indicator
        },
        success: function(response) {
            alert(response.message);
            location.reload();
        }
    });
}

// Retry failed notification
function retryNotification(id) {
    $.ajax({
        url: 'ajax/retry_notification.php',
        type: 'POST',
        data: { id: id },
        success: function(response) {
            alert(response.message);
            location.reload();
        }
    });
}

// Cancel notification
function cancelNotification(id) {
    if (!confirm('Are you sure you want to cancel this notification?')) return;
    
    $.ajax({
        url: 'ajax/cancel_notification.php',
        type: 'POST',
        data: { id: id },
        success: function(response) {
            alert(response.message);
            location.reload();
        }
    });
}

// Send new SMS
function sendNewSms() {
    const formData = {
        recipientType: $('#recipientType').val(),
        recipientId: $('#recipientType').val() === 'custom' ? null : $('#recipientSelect').val(),
        customNumber: $('#recipientType').val() === 'custom' ? $('#customNumber').val() : null,
        templateId: $('#smsTemplate').val(),
        message: $('#smsContent').val(),
        schedule: $('#smsSchedule').val(),
        sendDate: $('#smsSchedule').val() === 'later' ? $('#scheduleDate').val() : null
    };
    
    $.ajax({
        url: 'ajax/send_sms.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            alert(response.message);
            if (response.success) {
                $('#sendSmsModal').modal('hide');
                location.reload();
            }
        }
    });
    
    return false; // Prevent form submission
}

// Initialize
$(document).ready(function() {
    updateRecipients();
});
</script>
</body>
</html>