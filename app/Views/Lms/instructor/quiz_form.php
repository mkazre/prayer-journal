<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1><?= esc($title) ?></h1>
    <form method="post" id="quiz-form">
        <?= csrf_field() ?>
        <p><label>Title<br><input type="text" name="title" required></label></p>
        <p><label>Instructions<br><textarea name="instructions" rows="3"></textarea></label></p>
        <p><label>Pass Percent<br><input type="number" name="pass_percent" value="70"></label></p>

        <div id="questions"></div>
        <button type="button" class="pj-btn" onclick="addQuestion()">+ Add Question</button>
        <br><br>
        <button class="pj-btn pj-btn-gold" type="submit">Save Quiz</button>
    </form>
</div>
<script>
let qIndex = 0;
function addQuestion() {
    const i = qIndex++;
    const div = document.createElement('div');
    div.className = 'pj-card';
    div.style.marginTop = '1rem';
    div.innerHTML = `
        <p><label>Question<br><input type="text" name="questions[${i}][question]" required></label></p>
        <p><label>Points<br><input type="number" name="questions[${i}][points]" value="1"></label></p>
        ${[0,1,2,3].map(o => `<p><label><input type="radio" name="questions[${i}][correct]" value="${o}"> Option ${o+1}: <input type="text" name="questions[${i}][options][${o}]"></label></p>`).join('')}
    `;
    document.getElementById('questions').appendChild(div);
}
addQuestion();
</script>
<?= $this->endSection() ?>
