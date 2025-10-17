<?php
// File: index.php
// Simple single-file CRUD for Photo Studio Booking
// Instructions: create a MySQL database using provided SQL below (db.sql)
// Update DB credentials in the $config array before running.

// --- Configuration ---
$config = [
    'db_host' => '127.0.0.1',
    'db_name' => 'photo_studio',
    'db_user' => 'root',
    'db_pass' => '',
    'timezone' => 'Asia/Jakarta'
];

date_default_timezone_set($config['timezone']);

// --- PDO Connection ---
try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

// --- Simple router (based on `action` param) ---
$action = $_GET['action'] ?? 'list';

// Helper: sanitize input
function old($key, $default = '') {
    return htmlspecialchars($_POST[$key] ?? $default);
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_booking'])) {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $date = trim($_POST['date']);
        $time = trim($_POST['time']);
        $package = trim($_POST['package']);
        $notes = trim($_POST['notes']);

        $stmt = $pdo->prepare('INSERT INTO bookings (customer_name, phone, booking_date, booking_time, package, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$name, $phone, $date, $time, $package, $notes]);

        header('Location: index.php?success=created');
        exit;
    }

    if (isset($_POST['update_booking'])) {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $date = trim($_POST['date']);
        $time = trim($_POST['time']);
        $package = trim($_POST['package']);
        $notes = trim($_POST['notes']);

        $stmt = $pdo->prepare('UPDATE bookings SET customer_name = ?, phone = ?, booking_date = ?, booking_time = ?, package = ?, notes = ? WHERE id = ?');
        $stmt->execute([$name, $phone, $date, $time, $package, $notes, $id]);

        header('Location: index.php?success=updated');
        exit;
    }

    if (isset($_POST['delete_booking'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
        $stmt->execute([$id]);

        header('Location: index.php?success=deleted');
        exit;
    }
}

// --- Fetch data for views ---
function fetchBookings($pdo) {
    $q = $_GET['q'] ?? '';
    $sql = 'SELECT * FROM bookings ORDER BY booking_date ASC, booking_time ASC';
    if ($q) {
        $sql = 'SELECT * FROM bookings WHERE customer_name LIKE ? OR phone LIKE ? ORDER BY booking_date ASC, booking_time ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$q%","%$q%"]);
    } else {
        $stmt = $pdo->query($sql);
    }
    return $stmt->fetchAll();
}

// --- Page layout helpers ---
function flashMessage() {
    if (isset($_GET['success'])) {
        $map = ['created' => 'Booking created', 'updated' => 'Booking updated', 'deleted' => 'Booking deleted'];
        $k = $_GET['success'];
        if (isset($map[$k])) {
            echo '<div class="flash">' . htmlspecialchars($map[$k]) . '</div>';
        }
    }
}

// --- HTML Header ---
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Photo Studio Booking — Admin</title>
    <style>
        /* Simple, clean styles — tweak as you like */
        :root{--accent:#0b74de}
        body{font-family:Inter,system-ui,Segoe UI,Arial;margin:0;background:#f7fafc;color:#0f172a}
        .container{max-width:1000px;margin:32px auto;background:#fff;padding:20px;border-radius:12px;box-shadow:0 6px 18px rgba(12,16,28,0.06)}
        header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
        h1{font-size:20px;margin:0}
        a.btn{display:inline-block;padding:8px 12px;border-radius:8px;text-decoration:none;background:var(--accent);color:white}
        form .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
        label{display:block;font-size:13px;margin-bottom:6px}
        input[type=text],input[type=date],input[type=time],select,textarea{width:100%;padding:8px;border:1px solid #e6e9ef;border-radius:8px}
        table{width:100%;border-collapse:collapse;margin-top:12px}
        th,td{padding:10px;border-bottom:1px solid #eef2f7;text-align:left;font-size:14px}
        th{background:#fbfdff}
        .actions{display:flex;gap:8px}
        .small-btn{padding:6px 8px;border-radius:6px;text-decoration:none;border:1px solid #d1d5db;background:white}
        .danger{background:#ffeef0;border-color:#f8c0c8}
        .flash{padding:10px;background:#e6fffa;border:1px solid #b2f5ea;border-radius:8px;margin-bottom:12px}
        .search-row{display:flex;gap:8px;align-items:center}
        @media (max-width:600px){.actions{flex-direction:column}table th,table td{font-size:13px}}
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>Photo Studio Booking — Admin</h1>
        <div>
            <a class="btn" href="index.php?action=create">+ New Booking</a>
            <a class="small-btn" href="index.php">Refresh</a>
        </div>
    </header>

    <?php flashMessage(); ?>

    <?php if ($action === 'create'): ?>
        <h2>Create Booking</h2>
        <form method="post" action="index.php?action=create">
            <div class="grid">
                <div>
                    <label>Name</label>
                    <input type="text" name="name" required value="<?= old('name') ?>">
                </div>
                <div>
                    <label>Phone</label>
                    <input type="text" name="phone" required value="<?= old('phone') ?>">
                </div>
                <div>
                    <label>Date</label>
                    <input type="date" name="date" required value="<?= old('date') ?>">
                </div>
                <div>
                    <label>Time</label>
                    <input type="time" name="time" required value="<?= old('time') ?>">
                </div>
                <div>
                    <label>Package</label>
                    <select name="package">
                        <option value="Basic">Basic</option>
                        <option value="Standard">Standard</option>
                        <option value="Premium">Premium</option>
                    </select>
                </div>
                <div>
                    <label>Notes</label>
                    <textarea name="notes" rows="2"><?= old('notes') ?></textarea>
                </div>
            </div>
            <div style="margin-top:12px">
                <button type="submit" name="create_booking" class="btn">Save booking</button>
                <a href="index.php" class="small-btn">Cancel</a>
            </div>
        </form>

    <?php elseif ($action === 'edit' && isset($_GET['id'])):
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
        $stmt->execute([$id]);
        $booking = $stmt->fetch();
        if (!$booking) { echo '<p>Booking not found.</p>'; } else {
    ?>
        <h2>Edit Booking</h2>
        <form method="post" action="index.php?action=edit&id=<?= $booking['id'] ?>">
            <input type="hidden" name="id" value="<?= $booking['id'] ?>">
            <div class="grid">
                <div>
                    <label>Name</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($booking['customer_name']) ?>">
                </div>
                <div>
                    <label>Phone</label>
                    <input type="text" name="phone" required value="<?= htmlspecialchars($booking['phone']) ?>">
                </div>
                <div>
                    <label>Date</label>
                    <input type="date" name="date" required value="<?= htmlspecialchars($booking['booking_date']) ?>">
                </div>
                <div>
                    <label>Time</label>
                    <input type="time" name="time" required value="<?= htmlspecialchars($booking['booking_time']) ?>">
                </div>
                <div>
                    <label>Package</label>
                    <select name="package">
                        <option <?= $booking['package']==='Basic'? 'selected':'' ?>>Basic</option>
                        <option <?= $booking['package']==='Standard'? 'selected':'' ?>>Standard</option>
                        <option <?= $booking['package']==='Premium'? 'selected':'' ?>>Premium</option>
                    </select>
                </div>
                <div>
                    <label>Notes</label>
                    <textarea name="notes" rows="2"><?= htmlspecialchars($booking['notes']) ?></textarea>
                </div>
            </div>
            <div style="margin-top:12px">
                <button type="submit" name="update_booking" class="btn">Update</button>
                <a href="index.php" class="small-btn">Cancel</a>
            </div>
        </form>
    <?php } elseif ($action === 'list'): ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <form method="get" action="index.php" style="flex:1;margin-right:12px">
                <div class="search-row">
                    <input type="hidden" name="action" value="list">
                    <input type="text" name="q" placeholder="Search name or phone" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    <button class="small-btn">Search</button>
                </div>
            </form>
            <div style="text-align:right;font-size:13px;color:#566574">Timezone: <?= htmlspecialchars($config['timezone']) ?></div>
        </div>

        <?php
        \$bookings = fetchBookings(\$pdo);
        if (!count(\$bookings)) {
            echo '<p>No bookings yet. Click "New Booking" to add one.</p>';
        } else {
        ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Package</th>
                    <th>Notes</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $i => $b): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($b['customer_name']) ?></td>
                        <td><?= htmlspecialchars($b['phone']) ?></td>
                        <td><?= htmlspecialchars($b['booking_date']) ?></td>
                        <td><?= htmlspecialchars($b['booking_time']) ?></td>
                        <td><?= htmlspecialchars($b['package']) ?></td>
                        <td><?= htmlspecialchars($b['notes']) ?></td>
                        <td><?= htmlspecialchars($b['created_at']) ?></td>
                        <td class="actions">
                            <a class="small-btn" href="index.php?action=edit&id=<?= $b['id'] ?>">Edit</a>
                            <form method="post" action="index.php" style="display:inline" onsubmit="return confirm('Delete this booking?');">
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <button type="submit" name="delete_booking" class="small-btn danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php } ?>

    <?php else: ?>
        <p>Action not recognized.</p>
    <?php endif; ?>

    <footer style="margin-top:18px;font-size:13px;color:#64748b">Built with ❤️ by Senku-style assistant — Single-file demo. Update DB credentials at the top of this file.</footer>
</div>
</body>
</html>
