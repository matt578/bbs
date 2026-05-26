<?php include 'header.php'; ?>

<div class="container mt-4">
    <h2>Add Supplier</h2>

    <form action="add_supplier.php" method="POST">

        <div class="mb-3">
            <label>Supplier Name</label>
            <input type="text" name="supplier_name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Contact Person</label>
            <input type="text" name="contact_person" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control">
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control">
        </div>

        <div class="mb-3">
            <label>Facebook Page Link</label>
            <input type="url" name="facebook_link" class="form-control" placeholder="https://facebook.com/example">
        </div>

        <button type="submit" class="btn btn-success">Save Supplier</button>
        <a href="suppliers.php" class="btn btn-secondary">Cancel</a>

    </form>
</div>

<?php include 'footer.php'; ?>
