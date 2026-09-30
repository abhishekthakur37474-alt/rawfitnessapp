<?php
$isEdit = !empty($plan);
$action = $isEdit
    ? $base . '/workout-plans/' . (int) $plan['id']
    : $base . '/workout-plans';
$level = (string) ($plan['level'] ?? 'all');
$levels = ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'all' => 'All levels'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Edit workout plan' : 'New workout plan' ?></h1>
    <a class="btn btn-outline-light" href="<?= htmlspecialchars($base) ?>/workout-plans">&larr; Back</a>
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
                        <input class="form-control" name="category" value="<?= htmlspecialchars((string) ($plan['category'] ?? '')) ?>" placeholder="Strength, HIIT, Cardio...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Level</label>
                        <select class="form-select" name="level">
                            <?php foreach ($levels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= $level === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
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
        <h2 class="h5 mb-0">Days &amp; exercises</h2>
        <button class="btn btn-outline-light" type="button" id="addDay">+ Add day</button>
    </div>
    <div id="daysWrap">
        <?php foreach ($days as $i => $day): ?>
            <div class="card-panel p-3 mb-3 day-block" data-day="<?= (int) $i ?>">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="h6 mb-0">Day <?= (int) $i + 1 ?></h3>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-day">Remove day</button>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Day title</label>
                        <input class="form-control" name="days[<?= (int) $i ?>][title]" value="<?= htmlspecialchars((string) ($day['title'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Notes</label>
                        <input class="form-control" name="days[<?= (int) $i ?>][notes]" value="<?= htmlspecialchars((string) ($day['notes'] ?? '')) ?>">
                    </div>
                </div>
                <div class="exercises">
                    <?php foreach (($day['exercises'] ?? []) as $j => $ex): ?>
                        <div class="row g-2 exercise-row mb-2 align-items-end">
                            <div class="col-md-3">
                                <input class="form-control" name="days[<?= (int) $i ?>][exercises][<?= (int) $j ?>][name]" value="<?= htmlspecialchars((string) ($ex['name'] ?? '')) ?>" placeholder="Exercise name">
                            </div>
                            <div class="col-4 col-md-1">
                                <input class="form-control" name="days[<?= (int) $i ?>][exercises][<?= (int) $j ?>][sets]" value="<?= htmlspecialchars((string) ($ex['sets'] ?? '')) ?>" placeholder="Sets">
                            </div>
                            <div class="col-4 col-md-1">
                                <input class="form-control" name="days[<?= (int) $i ?>][exercises][<?= (int) $j ?>][reps]" value="<?= htmlspecialchars((string) ($ex['reps'] ?? '')) ?>" placeholder="Reps">
                            </div>
                            <div class="col-4 col-md-1">
                                <input class="form-control" name="days[<?= (int) $i ?>][exercises][<?= (int) $j ?>][rest]" value="<?= htmlspecialchars((string) ($ex['rest'] ?? '')) ?>" placeholder="Rest">
                            </div>
                            <div class="col-8 col-md-2">
                                <input class="form-control" name="days[<?= (int) $i ?>][exercises][<?= (int) $j ?>][image]" value="<?= htmlspecialchars((string) ($ex['image'] ?? '')) ?>" placeholder="Image URL">
                            </div>
                            <div class="col-8 col-md-3">
                                <input class="form-control" name="days[<?= (int) $i ?>][exercises][<?= (int) $j ?>][notes]" value="<?= htmlspecialchars((string) ($ex['notes'] ?? '')) ?>" placeholder="Notes">
                            </div>
                            <div class="col-1">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-exercise">x</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline-light add-exercise">Add exercise</button>
            </div>
        <?php endforeach; ?>
    </div>

    <button class="btn btn-warning mt-2" type="submit"><?= $isEdit ? 'Save changes' : 'Create plan' ?></button>
</form>

<template id="dayTemplate">
    <div class="card-panel p-3 mb-3 day-block">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h3 class="h6 mb-0">Day</h3>
            <button type="button" class="btn btn-sm btn-outline-danger remove-day">Remove day</button>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <label class="form-label">Day title</label>
                <input class="form-control" name="days[__I__][title]">
            </div>
            <div class="col-md-6">
                <label class="form-label">Notes</label>
                <input class="form-control" name="days[__I__][notes]">
            </div>
        </div>
        <div class="exercises"></div>
        <button type="button" class="btn btn-sm btn-outline-light add-exercise">Add exercise</button>
    </div>
</template>

<template id="exerciseTemplate">
    <div class="row g-2 exercise-row mb-2 align-items-end">
        <div class="col-md-3">
            <input class="form-control" name="days[__D__][exercises][__E__][name]" placeholder="Exercise name">
        </div>
        <div class="col-4 col-md-1">
            <input class="form-control" name="days[__D__][exercises][__E__][sets]" placeholder="Sets">
        </div>
        <div class="col-4 col-md-1">
            <input class="form-control" name="days[__D__][exercises][__E__][reps]" placeholder="Reps">
        </div>
        <div class="col-4 col-md-1">
            <input class="form-control" name="days[__D__][exercises][__E__][rest]" placeholder="Rest">
        </div>
        <div class="col-8 col-md-2">
            <input class="form-control" name="days[__D__][exercises][__E__][image]" placeholder="Image URL">
        </div>
        <div class="col-8 col-md-3">
            <input class="form-control" name="days[__D__][exercises][__E__][notes]" placeholder="Notes">
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-sm btn-outline-danger remove-exercise">x</button>
        </div>
    </div>
</template>

<script>
(function () {
    var uid = 0;
    var wrap = document.getElementById('daysWrap');
    var dayTpl = document.getElementById('dayTemplate').innerHTML;
    var exTpl = document.getElementById('exerciseTemplate').innerHTML;

    function addExercise(block) {
        var dayIndex = block.getAttribute('data-day');
        var exIndex = 'n' + (uid++);
        var html = exTpl.split('__D__').join(dayIndex).split('__E__').join(exIndex);
        var holder = document.createElement('div');
        holder.innerHTML = html.trim();
        var row = holder.firstElementChild;
        row.querySelector('.remove-exercise').addEventListener('click', function () { row.remove(); });
        block.querySelector('.exercises').appendChild(row);
    }

    function bindDay(block) {
        block.querySelector('.remove-day').addEventListener('click', function () { block.remove(); });
        block.querySelector('.add-exercise').addEventListener('click', function () { addExercise(block); });
        Array.prototype.forEach.call(block.querySelectorAll('.remove-exercise'), function (btn) {
            btn.addEventListener('click', function () { btn.closest('.exercise-row').remove(); });
        });
    }

    function addDay() {
        var dayIndex = 'd' + (uid++);
        var html = dayTpl.split('__I__').join(dayIndex);
        var holder = document.createElement('div');
        holder.innerHTML = html.trim();
        var block = holder.firstElementChild;
        block.setAttribute('data-day', dayIndex);
        wrap.appendChild(block);
        block.querySelector('h3').textContent = 'Day ' + wrap.querySelectorAll('.day-block').length;
        bindDay(block);
        addExercise(block);
    }

    document.getElementById('addDay').addEventListener('click', addDay);
    Array.prototype.forEach.call(document.querySelectorAll('.day-block'), bindDay);
})();
</script>
