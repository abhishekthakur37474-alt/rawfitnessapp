<?php
$isEdit = !empty($plan);
$action = $isEdit
    ? $base . '/diet-plans/' . (int) $plan['id']
    : $base . '/diet-plans';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Edit diet plan' : 'New diet plan' ?></h1>
    <a class="btn btn-outline-light" href="<?= htmlspecialchars($base) ?>/diet-plans">&larr; Back</a>
</div>
<form method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($action) ?>">
    <?= Csrf::field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-panel p-4">
                <h2 class="h5 mb-3">Plan details</h2>
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input class="form-control" name="title" value="<?= htmlspecialchars((string) ($plan['title'] ?? '')) ?>" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <input class="form-control" name="category" value="<?= htmlspecialchars((string) ($plan['category'] ?? '')) ?>" placeholder="Weight Loss, Weight Gain...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Calories (kcal)</label>
                        <input class="form-control" type="number" min="0" name="calories" value="<?= htmlspecialchars((string) ($plan['calories'] ?? '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars((string) ($plan['description'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" <?= !$isEdit || (int) $plan['is_active'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-panel p-4">
                <h2 class="h5 mb-3">Cover image</h2>
                <?php if (!empty($imageUrl)): ?>
                    <img class="gov-thumb mb-2" src="<?= htmlspecialchars((string) $imageUrl) ?>" alt="Plan image">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeImage">
                        <label class="form-check-label" for="removeImage">Remove current image</label>
                    </div>
                <?php endif; ?>
                <input class="form-control" type="file" name="image" accept="image/jpeg,image/png">
                <p class="text-secondary small mt-2 mb-0">JPG or PNG, up to 5MB.</p>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h2 class="h5 mb-0">Meals</h2>
        <button class="btn btn-outline-light" type="button" id="addMeal">+ Add meal</button>
    </div>
    <div id="mealsWrap">
        <?php foreach ($meals as $i => $meal): ?>
            <div class="card-panel p-3 mb-2 meal-row">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Meal type</label>
                        <input class="form-control" name="meals[<?= (int) $i ?>][meal_type]" value="<?= htmlspecialchars((string) ($meal['meal_type'] ?? '')) ?>" placeholder="Breakfast">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Items</label>
                        <input class="form-control" name="meals[<?= (int) $i ?>][items]" value="<?= htmlspecialchars((string) ($meal['items'] ?? '')) ?>" placeholder="Oats, eggs, banana">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Calories</label>
                        <input class="form-control" type="number" min="0" name="meals[<?= (int) $i ?>][calories]" value="<?= htmlspecialchars((string) ($meal['calories'] ?? '')) ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Time</label>
                        <input class="form-control" name="meals[<?= (int) $i ?>][time]" value="<?= htmlspecialchars((string) ($meal['meal_time'] ?? '')) ?>" placeholder="08:00">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-meal w-100">x</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <button class="btn btn-warning mt-2" type="submit"><?= $isEdit ? 'Save changes' : 'Create plan' ?></button>
</form>

<template id="mealTemplate">
    <div class="card-panel p-3 mb-2 meal-row">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Meal type</label>
                <input class="form-control" name="meals[__I__][meal_type]" placeholder="Breakfast">
            </div>
            <div class="col-md-5">
                <label class="form-label">Items</label>
                <input class="form-control" name="meals[__I__][items]" placeholder="Oats, eggs, banana">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Calories</label>
                <input class="form-control" type="number" min="0" name="meals[__I__][calories]">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Time</label>
                <input class="form-control" name="meals[__I__][time]" placeholder="08:00">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-outline-danger remove-meal w-100">x</button>
            </div>
        </div>
    </div>
</template>

<script>
(function () {
    var uid = 0;
    var wrap = document.getElementById('mealsWrap');
    var tpl = document.getElementById('mealTemplate').innerHTML;

    function bind(row) {
        row.querySelector('.remove-meal').addEventListener('click', function () { row.remove(); });
    }

    document.getElementById('addMeal').addEventListener('click', function () {
        var html = tpl.split('__I__').join('m' + (uid++));
        var holder = document.createElement('div');
        holder.innerHTML = html.trim();
        var row = holder.firstElementChild;
        wrap.appendChild(row);
        bind(row);
    });

    Array.prototype.forEach.call(document.querySelectorAll('.meal-row'), bind);
})();
</script>
