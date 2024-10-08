<?php
include_once 'headtags.php';
$settings = [
    'publicize_result', 'receive_statistics',
    'allow_multiple_voting', 'paginate_question', 'allow_skip_question'
];
$canEdit = hasPermission("questions", "add", $metadata);
$canDelete = hasPermission("questions", "add", $metadata);
?>
<div class="container pt-4" style="max-width:1000px;">
    <div class="d-flex justify-content-between">
        <div></div>
        <div>
            <a class="btn btn-outline-primary btn-sm" href="<?= $baseURL ?>dashboard">
                <i class="fa fa-list"></i> List Surveys
            </a>
            <?php if( !empty($isFound) && hasPermission("surveys", "update", $metadata)) { ?>
                <a class="btn btn-sm btn-outline-success" href="<?= $baseURL ?>surveys/modify/<?= $slug ?>/edit">
                    <i class="fa fa-cog"></i> Configuration
                </a>
            <?php } ?>
        </div>
    </div>
    <div class="alert alert-success mb-0 mt-2">
        Here you can edit your questions or add new ones.
    </div>
    <div class="row mb-4 pb-3">
        <div class="col-lg-12 mt-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <div><h3><?= $survey['title'] ?></h3></div>
                    <div>
                        <?php if(hasPermission("questions", "update", $metadata)) { ?>
                            <div class="text-center w-100">
                                <button onclick="return add_question('<?= $slug; ?>')" class="btn btn-button btn-secondary">
                                    <i class="fa fa-plus"></i> Add Question
                                </button>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="card-body bg-white">
                    <div class="new-question"></div>
                    <?php if(isset($survey['questions'])) { ?>
                        <?php if(!empty($survey['questions']) && is_array($survey['questions'])) { ?>
                            <?php foreach($survey['questions'] as $i => $question) { ?>
                                <div class="question-wrapper p-3">
                                    <?= format_question($question, null, $slug, true, ['canEdit' => $canEdit, 'canDelete' => $canDelete], ($i + 1)); ?>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once 'foottags.php'; ?>