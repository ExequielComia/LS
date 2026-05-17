<<<<<<< HEAD
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RIDER</title>
    <link rel="stylesheet" href="../css/style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/logodash.png">
</head>
<style>
    /* Custom scrollbar styling - transparent and thin */
    .heading div[style*="overflow-y: auto"]::-webkit-scrollbar {
        width: 5px;
    }

    .heading div[style*="overflow-y: auto"]::-webkit-scrollbar-track {
        background: transparent;
    }

    .heading div[style*="overflow-y: auto"]::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 10px;
    }

    .heading div[style*="overflow-y: auto"]::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    .rider-stats {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }

    .stat-card {
        background: white;
        border: 2px solid #e8dcc8;
        border-radius: 12px;
        padding: 1rem;
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .deliveries-card {
        background: white;
        border: 2px solid saddlebrown;
        border-radius: 12px;
        overflow: hidden;
    }

    .card-header {
        background: #fef7e8;
        padding: 1rem;
        border-bottom: 2px solid #e8dcc8;
        display: flex;
        justify-content: space-between;
    }

    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }

    .status-completed {
        background: #d4edda;
        color: #1a5c2a;
    }

    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }

    .status-pending {
        background: #fff3cd;
        color: #7a4f00;
    }

    .table-container {
        overflow-x: auto;
        max-height: 400px;
        overflow-y: auto;
    }

    .delivery-table {
        width: 100%;
        border-collapse: collapse;
    }

    .delivery-table th,
    .delivery-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #f0ebe0;
    }

    .badge {
        background: #fff3cd;
        color: #7a4f00;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
    }

    .view-btn {
        background: saddlebrown;
        border: none;
        padding: 5px 15px;
        border-radius: 20px;
        color: white;
        cursor: pointer;
        font-size: 12px;
    }

    .view-btn:hover {
        background: #6b3a1f;
    }
=======
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RIDER</title>
    <link rel="stylesheet" href="../css/style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/logodash.png">
</head>
<style>
    /* Custom scrollbar styling - transparent and thin */
    .heading div[style*="overflow-y: auto"]::-webkit-scrollbar {
        width: 5px;
    }

    .heading div[style*="overflow-y: auto"]::-webkit-scrollbar-track {
        background: transparent;
    }

    .heading div[style*="overflow-y: auto"]::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 10px;
    }

    .heading div[style*="overflow-y: auto"]::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    .rider-stats {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }

    .stat-card {
        background: white;
        border: 2px solid #e8dcc8;
        border-radius: 12px;
        padding: 1rem;
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .deliveries-card {
        background: white;
        border: 2px solid saddlebrown;
        border-radius: 12px;
        overflow: hidden;
    }

    .card-header {
        background: #fef7e8;
        padding: 1rem;
        border-bottom: 2px solid #e8dcc8;
        display: flex;
        justify-content: space-between;
    }

    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }

    .status-completed {
        background: #d4edda;
        color: #1a5c2a;
    }

    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }

    .status-pending {
        background: #fff3cd;
        color: #7a4f00;
    }

    .table-container {
        overflow-x: auto;
        max-height: 400px;
        overflow-y: auto;
    }

    .delivery-table {
        width: 100%;
        border-collapse: collapse;
    }

    .delivery-table th,
    .delivery-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #f0ebe0;
    }

    .badge {
        background: #fff3cd;
        color: #7a4f00;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
    }

    .view-btn {
        background: saddlebrown;
        border: none;
        padding: 5px 15px;
        border-radius: 20px;
        color: white;
        cursor: pointer;
        font-size: 12px;
    }

    .view-btn:hover {
        background: #6b3a1f;
    }
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</style>