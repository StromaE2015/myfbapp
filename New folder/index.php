<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fakebook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body {
            background-color: #f0f2f5;
        }
        .sidebar, .profile-sidebar, .main-content {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            margin-left: 5px;
        }
        .navbar {
            background-color: #1877f2;
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .navbar .nav-link:hover {
            color: #dfe3ee !important;
        }
        .sidebar h2, .profile-sidebar h4 {
            color: #1877f2;
        }
        .list-group-item {
            cursor: pointer;
        }
        .list-group-item:hover {
            background-color: #e4e6eb;
        }
        .btn-primary { background-color: #1877f2; border: none; }
        .btn-success { background-color: #42b72a; border: none; }
        .btn-warning { background-color: #f7b928; border: none; }
        .form-control {
            margin: 10px 0;
            padding: 10px;
        }
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }
            .sidebar, .profile-sidebar, .main-content {
                padding: 15px;
            }
        }
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="assets/js/notifications.js"></script>
    </style>
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Fakebook</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-home"></i> Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-users"></i> Friends</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-users-cog"></i> Groups</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-bookmark"></i> Bookmark</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-pen"></i> Write</a></li>

                <!-- أيقونة الإشعارات -->
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="badge bg-danger" id="notificationCount">3</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" id="notificationsList">
                        <li><a class="dropdown-item" href="#">إشعار 1</a></li>
                        <li><a class="dropdown-item" href="#">إشعار 2</a></li>
                        <li><a class="dropdown-item" href="#">إشعار 3</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="#">عرض كل الإشعارات</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

    <div class="container mt-4">
        <div class="row">
            <aside class="col-lg-3 col-md-4 sidebar">
                <h2 class="text-center">FAKEBOOK</h2>
                <ul class="list-group">
                    <li class="list-group-item">News Timeline</li>
                    <li class="list-group-item">Friends</li>
                    <li class="list-group-item">Groups</li>
                    <li class="list-group-item">Bookmark</li>
                    <li class="list-group-item">Write</li>
                </ul>
            </aside>
            <main class="col-lg-5 col-md-8 main-content">
                <div class="mb-3">
                    <input type="text" class="form-control" placeholder="What You Think On?">
                    <div class="mt-2 d-flex gap-2 flex-wrap">
                        <button class="btn btn-primary">Go Live</button>
                        <button class="btn btn-success">Upload Photo</button>
                        <button class="btn btn-warning">Feelings</button>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Donatello the turtle</h5>
                        <p class="card-text">Just learned how to use the computer, congratulate me!</p>
                        <div class="mt-2 d-flex gap-2">
                            <button class="btn btn-outline-danger">❤️</button>
                            <button class="btn btn-outline-primary">💬</button>
                            <button class="btn btn-outline-secondary">🔄</button>
                        </div>
                    </div>
                </div>
            </main>
            <aside class="col-lg-3 col-md-12 profile-sidebar text-center">
                <img src="profile.jpg" class="img-fluid rounded-circle mb-2" alt="Profile Picture">
                <h4>Ahmed Mohamed Taha</h4>
                <p>22-year-old Egyptian Front-end Developer.</p>
                <ul class="list-group">
                    <li class="list-group-item">My Profile</li>
                    <li class="list-group-item">Settings</li>
                    <li class="list-group-item">Privacy</li>
                    <li class="list-group-item">Help & Support</li>
                    <li class="list-group-item">Logout</li>
                </ul>
            </aside>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
