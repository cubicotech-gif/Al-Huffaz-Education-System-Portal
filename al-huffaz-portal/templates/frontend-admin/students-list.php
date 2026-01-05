<?php
/**
 * Front-end Admin Students List Template
 * Al-Huffaz Education System Portal
 *
 * Standalone students list for front-end
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
.ahp-students-list {
    font-family: 'Poppins', sans-serif;
    padding: 24px;
    background: #f8fafc;
    border-radius: 16px;
}

.ahp-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.ahp-list-title {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.ahp-toolbar {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.ahp-search-box {
    flex: 1;
    min-width: 250px;
    position: relative;
}

.ahp-search-box input {
    width: 100%;
    padding: 10px 14px 10px 40px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
}

.ahp-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}

.ahp-filter-select {
    padding: 10px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    min-width: 140px;
}

.ahp-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
}

.ahp-card-body {
    padding: 0;
}

.ahp-table-wrapper {
    overflow-x: auto;
}

.ahp-table {
    width: 100%;
    border-collapse: collapse;
}

.ahp-table th,
.ahp-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.ahp-table thead th {
    background: #f8fafc;
    font-weight: 600;
    color: #1e293b;
    font-size: 12px;
    text-transform: uppercase;
}

.ahp-table tbody tr:hover {
    background: #f8fafc;
}

.ahp-student-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ahp-student-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
}

.ahp-student-avatar-placeholder {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #0080ff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
}

.ahp-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 600;
}

.ahp-badge-primary { background: #dbeafe; color: #1e40af; }
.ahp-badge-success { background: #d1fae5; color: #065f46; }

.ahp-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    text-decoration: none;
    cursor: pointer;
    border: none;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s;
}

.ahp-btn-primary {
    background: linear-gradient(135deg, #0080ff, #004d99);
    color: white;
}

.ahp-btn-secondary {
    background: #f8fafc;
    color: #1e293b;
    border: 1px solid #e2e8f0;
}

.ahp-btn-danger {
    background: #ef4444;
    color: white;
}

.ahp-btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    justify-content: center;
}

.ahp-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.ahp-actions {
    display: flex;
    gap: 6px;
}

.ahp-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px;
}

.ahp-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: #0080ff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.ahp-pagination {
    text-align: center;
    padding: 20px;
}

.ahp-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 14px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    z-index: 9999;
    display: none;
}

.ahp-toast.success { background: #10b981; }
.ahp-toast.error { background: #ef4444; }

@media (max-width: 768px) {
    .ahp-toolbar {
        flex-direction: column;
    }
    .ahp-search-box {
        min-width: 100%;
    }
    .ahp-list-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="ahp-students-list">
    <div class="ahp-list-header">
        <h2 class="ahp-list-title"><i class="fas fa-users"></i> <?php _e('Students', 'al-huffaz-portal'); ?></h2>
    </div>

    <div class="ahp-toolbar">
        <div class="ahp-search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="studentSearch" placeholder="<?php _e('Search by name or GR number...', 'al-huffaz-portal'); ?>">
        </div>
        <select class="ahp-filter-select" id="filterGrade">
            <option value=""><?php _e('All Grades', 'al-huffaz-portal'); ?></option>
            <option value="kg1">KG 1</option>
            <option value="kg2">KG 2</option>
            <option value="class1">Class 1</option>
            <option value="class2">Class 2</option>
            <option value="class3">Class 3</option>
            <option value="level1">Level 1</option>
            <option value="level2">Level 2</option>
            <option value="level3">Level 3</option>
            <option value="shb">SHB</option>
            <option value="shg">SHG</option>
        </select>
        <select class="ahp-filter-select" id="filterCategory">
            <option value=""><?php _e('All Categories', 'al-huffaz-portal'); ?></option>
            <option value="hifz">Hifz</option>
            <option value="nazra">Nazra</option>
            <option value="qaidah">Qaidah</option>
        </select>
    </div>

    <div class="ahp-card">
        <div class="ahp-card-body">
            <div class="ahp-table-wrapper">
                <table class="ahp-table" id="studentsTable">
                    <thead>
                        <tr>
                            <th><?php _e('Student', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('GR #', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Grade', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Category', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Father', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Actions', 'al-huffaz-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody">
                        <tr><td colspan="6" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="studentsPagination" class="ahp-pagination"></div>
</div>

<div class="ahp-toast" id="toast"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;

    function loadStudents(page = 1) {
        currentPage = page;
        const search = document.getElementById('studentSearch').value;
        const grade = document.getElementById('filterGrade').value;
        const category = document.getElementById('filterCategory').value;

        document.getElementById('studentsTableBody').innerHTML = '<tr><td colspan="6" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'alhuffaz_get_students',
                nonce: '<?php echo wp_create_nonce('alhuffaz_student_nonce'); ?>',
                page: page,
                search: search,
                grade: grade,
                category: category,
                per_page: 20
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderStudents(data.data.students);
                renderPagination(data.data.total_pages, page);
            } else {
                document.getElementById('studentsTableBody').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:#64748b;">Error loading students</td></tr>';
            }
        })
        .catch(err => {
            document.getElementById('studentsTableBody').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:#64748b;">Error loading students</td></tr>';
        });
    }

    function renderStudents(students) {
        const tbody = document.getElementById('studentsTableBody');
        if (!students || students.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:#64748b;"><?php _e('No students found', 'al-huffaz-portal'); ?></td></tr>';
            return;
        }

        tbody.innerHTML = students.map(s => `
            <tr>
                <td>
                    <div class="ahp-student-cell">
                        ${s.photo ? `<img src="${s.photo}" class="ahp-student-avatar">` : `<div class="ahp-student-avatar-placeholder">${(s.name || 'S').charAt(0).toUpperCase()}</div>`}
                        <span>${s.name || '-'}</span>
                    </div>
                </td>
                <td>${s.gr_number || '-'}</td>
                <td><span class="ahp-badge ahp-badge-primary">${(s.grade_level || '-').toUpperCase()}</span></td>
                <td><span class="ahp-badge ahp-badge-success">${s.islamic_studies_category ? s.islamic_studies_category.charAt(0).toUpperCase() + s.islamic_studies_category.slice(1) : '-'}</span></td>
                <td>${s.father_name || '-'}</td>
                <td>
                    <div class="ahp-actions">
                        <a href="${s.permalink || '#'}" class="ahp-btn ahp-btn-secondary ahp-btn-icon" target="_blank" title="View"><i class="fas fa-eye"></i></a>
                        <button class="ahp-btn ahp-btn-danger ahp-btn-icon" onclick="deleteStudent(${s.id})" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(totalPages, current) {
        const container = document.getElementById('studentsPagination');
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        for (let i = 1; i <= Math.min(totalPages, 10); i++) {
            html += `<button class="ahp-btn ${i === current ? 'ahp-btn-primary' : 'ahp-btn-secondary'}" onclick="loadStudentsPage(${i})" style="margin: 0 4px;">${i}</button>`;
        }
        container.innerHTML = html;
    }

    window.loadStudentsPage = function(page) {
        loadStudents(page);
    };

    window.deleteStudent = function(id) {
        if (!confirm('<?php _e('Are you sure you want to delete this student?', 'al-huffaz-portal'); ?>')) {
            return;
        }

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'alhuffaz_delete_student',
                nonce: '<?php echo wp_create_nonce('alhuffaz_student_nonce'); ?>',
                student_id: id
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Student deleted successfully', 'success');
                loadStudents(currentPage);
            } else {
                showToast(data.data?.message || 'Error deleting student', 'error');
            }
        });
    };

    function showToast(message, type) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'ahp-toast ' + type;
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    // Search and Filter
    let searchTimeout;
    document.getElementById('studentSearch').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadStudents(1), 300);
    });

    document.getElementById('filterGrade').addEventListener('change', () => loadStudents(1));
    document.getElementById('filterCategory').addEventListener('change', () => loadStudents(1));

    // Initial load
    loadStudents();
});
</script>
