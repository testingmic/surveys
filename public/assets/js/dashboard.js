$(`span[class~="trix-button-group--file-tools"], span[class~="trix-button-group-spacer"], span[class~="trix-button-group--history-tools"]`).remove();
var activeQuestion;
var _html_entities = (str) => {
    return String(str).replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

var add_option = (question_id = 'new_question') => {
    let data = $(`div[class="option-container"][id="question_id_${question_id}"] div[data-option_id]:last`).data();
    let next_row = typeof data !=='undefined' && typeof data.option_id !== 'undefined' ? (data.option_id + 1) : 1;

    let option_row = `
    <div class="form-group mb-2" data-question="${question_id}" data-option_id="${next_row}">
        <div class="d-flex justify-content-between">
            <div class="col-11">
                <input type="text" name="option[${next_row}]" id="option[${next_row}]" class="form-control">
            </div>
            <div>
                <button type="button" onclick="return remove_option('${next_row}')" class="btn btn-outline-danger btn-sm">
                    <i class="fa fa-times-circle"></i>
                </button>
            </div>
        </div>
    </div>`;
    $(`div[class="option-container"][id="question_id_${question_id}"]`).append(option_row);

}

var populate_statistics = function(results = "default", question_id) {

    let theSurvey = results == "default" ? surveyResults : results;
    let questions = theSurvey.questions;

    if(typeof questions !== 'undefined') {
        let html_string = "";
        let e = questions[question_id];

        if(typeof e == 'undefined') {
            Notify("Sorry! The question id does not exist");
            return false;
        }
        
        let options = e.grouping;
        html_string += `<div class='mb-5'>`;
        html_string += `<div class='pb-1'><strong>${e.title}</strong></div>`;
        $.each(options, function(ii, ee) {
            let label = ee.count == 1 || ee.count == 0 ? "vote" : "votes";
            html_string += `
            <div class="d-flex justify-content-between border-bottom mb-2 pb-2">
                <div>
                    ${ii}
                </div>
                <div>
                    <div class="progress-bar-container mt-0 text-left">
                        <div class="progress-bar mt-1" title="${ee.count} votes">
                            <div class="progress-bar-completed" style="${ii == "skipped" ? "background: #ff5722;" : ""}width: ${ee.percentage}%;${ee.leading !== undefined ? "background: #8bc34a;": ""}"></div>
                        </div>
                        <div class="d-flex justify-content-end" style="width:130px;font-size:14px">
                            <div style="margin-right:10px">
                                ${ee.count} ${label}
                            </div>
                            <div class="badge bg-secondary" style="width:40px;">
                                ${ee.percentage}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        });
        html_string += `<div class="p-2 bg-secondary">
            <span style="font-size: 18px; margin-right: 20px" class="text-white">
                Votes: ${e.votes_cast}
            </span>
            <span style="font-size: 18px; margin-right: 20px" class="text-white">
                Skipped: ${e.skipped}
            </span>
            <span style="font-size: 18px; float:right" class="text-white float-right">
                <strong>Total:</strong> ${e.votes_cast + e.skipped}
            </span>
        </div>`;
        html_string += "</div>";
        $(`div[id="answer_question"]`).html(html_string);
    }
    
}

var delete_question = (question_id) => {
    swal({
        title: "Delete Question",
        text: "Are you sure you want to delete this question? You cannot reverse the action once confirmed.",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    }).then((proceed) => {
        if (proceed) {
            $(`div[data-question_id="${question_id}"] div[class="formoverlay"]`).show();
            $.post(`${baseURL}surveys/deletequestion/${question_id}`).then((response) => {
                Notify(response.data.result, responseCode(response.code));
                if(response.code == 200) {
                    $(`div[class~="questionnaire"][data-question_id="${question_id}"]`).remove();
                }
            }).fail((err) => {
                Notify("Sorry! An unexpected error occured while processing the request.");
                $(`div[data-question_id="${question_id}"] div[class="formoverlay"]`).hide();
            });
        }
    });
}

var cancel_update = () => {
    $(`div[class="new-question"]`).html(``);
    $(`div[data-question_id="${selectedQuestion}"] div[class~='question']`).removeClass("hidden");
    $(`div[data-question_id="${selectedQuestion}"] div[class="question_content"]`).html(``);
    $(`div[class~="questionnaire"][data-question_id="${selectedQuestion}"] div[data-item='hover']`).addClass("hovercontrol");
}

var add_instruction = () => {
    $(`span[class~="instruction"]`).remove();
    $(`textarea[name="instructions"]`).removeClass("hidden");
    $(`div[class~="instructions-container"]`).removeClass("hidden");
}

var remove_option = (option_id) => {
    $(`div[data-option_id="${option_id}"]`).remove();
}

var save_question = () => {
    $(`form[class="questionForm"]`).on("submit", function(evt) {
        evt.preventDefault();
        
        let form = $(`form[class="questionForm"]`).serializeArray();
        
        $(`form[class="questionForm"] div[class="formoverlay"]`).show();

        $(`form[class="questionForm"] button[type="submit"]`)
            .attr({'disabled': true})
            .html(`<i class="fa fa-spin fa-spinner"></i>`);

        $.post(`${baseURL}surveys/savequestion`, form).then((response) => {

            $(`form[class="questionForm"] div[class="formoverlay"]`).hide();
            $(`form[class="questionForm"] button[type="submit"]`).attr({'disabled': false}).html(`Save`);
            
            Notify(response.data.result, responseCode(response.code));
            
            if(response.code == 200) {
                if(typeof response.data.additional !== 'undefined') {
                    if(typeof response.data.additional.clear !== 'undefined') {
                        $(`form[class="appForm"] *`).val(``);
                    }
                    if(typeof response.data.additional.href !== 'undefined') {
                        setTimeout(() => {
                            window.location.href = `${baseURL}${response.data.additional.href}`;
                        }, 2000);
                    }
                    if(response.data.additional.question) {
                        $(`div[class~='questionnaire'][data-question_id='${selectedQuestion}']`).html(response.data.additional.question);
                    }
                }
            }

        }).fail((err) => {
            $(`form[class="questionForm"] div[class="formoverlay"]`).hide();
            Notify("Sorry! An unexpected error occurred while processing the request.");
            $(`form[class="questionForm"] button[type="submit"]`).attr({'disabled': false}).html(`Save`);
        });

    });
}

var trigger_edit = function(slug, question_id) {
    
    $(`div[class="formoverlay"]`).hide();
    $(`div[class="new-question"]`).html(``);
    $(`div[data-question_id="${question_id}"] div[class="formoverlay"]`).show();

    $.get(`${baseURL}surveys/loadquestion/${slug}/${question_id}`).then((response) => {

        $(`div[data-question_id="${selectedQuestion}"] div[class~='question']`).removeClass("hidden");
        $(`div[data-question_id="${selectedQuestion}"] div[class="question_content"]`).html(``);
        $(`div[class~="questionnaire"][data-question_id="${question_id}"] div[data-item='hover']`).removeClass("hovercontrol");

        if(response.code == 200) {

            if(selectedQuestion !== question_id) {
                $(`div[class~="questionnaire"][data-question_id="${selectedQuestion}"] div[data-item='hover']`).addClass("hovercontrol");
            }

            selectedQuestion = question_id;
            $(`div[data-question_id="${question_id}"] div[class~='question']`).addClass("hidden");
            $(`div[data-question_id="${question_id}"] div[class="question_content"]`).html(response.data.result);
            save_question();
        } else {
            Notify(response.data.result);
        }

        $(`div[data-question_id="${question_id}"] div[class="formoverlay"]`).hide();

    }).fail((err) => {
        Notify("Sorry! An unexpected error occured while processing the request.");
        $(`div[data-question_id="${question_id}"] div[class="formoverlay"]`).hide();
    });
}

var add_question = function(slug) {
    $(`div[data-question_id="${selectedQuestion}"] div[class~='question']`).removeClass("hidden");
    $(`div[data-question_id="${selectedQuestion}"] div[class="question_content"]`).html(``);
    $(`div[class~="questionnaire"][data-question_id="${selectedQuestion}"] div[data-item='hover']`).addClass("hovercontrol");

    $.get(`${baseURL}surveys/loadquestion/${slug}`).then((response) => {
        if(response.code == 200) {
            $(`div[class="new-question"]`).html(response.data.result);
            save_question();
        } else {
            Notify(response.data.result, responseCode(response.code));
        }
    });
}

var release_form = () => {
    formoverlay.hide();
    $(`form[class="appForm"] button[type="submit"]`).attr({'disabled': false});
    $(`button[type="submit"]`).html(`<i class="fa fa-save"></i> Save`);
}

$(`form[class="appForm"]`).on("submit", function(evt) {
    evt.preventDefault();
    
    let form = $(`form[class="appForm"]`).serializeArray();
    let action = $(`form[class="appForm"]`).attr("action");

    $(`form[class="appForm"] button[type="submit"]`).attr({'disabled': true});
    $(`button[type="submit"]`).html(`<i class="fa fa-spin fa-spinner"></i>`);

    $.each($(`trix-editor`), function() {
        let trix_name = $(this).attr("name");
        let trix_content = $(this).html().replace(/<!--block-->/ig, '');
        let trix = {
            'name': trix_name,
            'value': _html_entities(trix_content)
        };
        form.push(trix);
    });

    formoverlay.show();
    $.post(`${action}`, form).then((response) => {
        Notify(response.data.result, responseCode(response.code));
        if(response.code == 200) {
            if(typeof response.data.additional !== 'undefined') {
                if(typeof response.data.additional.clear !== 'undefined') {
                    $(`form[class="appForm"] *`).val(``);
                    $(`trix-editor`).html(``);
                }
                if(typeof response.data.additional.href !== 'undefined') {
                    setTimeout(() => {
                        window.location.href = `${baseURL}${response.data.additional.href}`;
                    }, 2000);
                }
            }
        }
        
        if(typeof release_form == 'function') {
            release_form();
        }

    }).fail((err) => {
        if(typeof release_form == 'function') {
            release_form();
        }
        Notify("Sorry! An unexpected error occurred while processing the request.");
    });
});

if($(`input[name="surveyAnalytic"]`).length) {
    let data = $(`input[name="surveyAnalytic"]`).data();
    formoverlay.show();

    $.get(`${baseURL}surveys/results`, data).then((response) => {
        formoverlay.hide();
        if(response.code == 200) {
            surveyResults = response.data.result;
            let questions = response.data.result.questions;
            if(typeof questions !== 'undefined') {
                let ct = 0;
                $.each(questions, function(i, e) {
                    ct++;
                    $(`select[name="question_id"]`).append(`<option value="${i}">${ct}. ${e.title}</option>`);
                });
                if(!Object.keys(questions).length) {
                    $(`div[id="answer_question"]`).html(`<div class="alert alert-success mb-0">No questions found in this survey.</div>`);
                } else {
                    activeQuestion = surveyResults.first_question;
                    populate_statistics(surveyResults, activeQuestion);
                }
            }
        }
    }).fail((err) => {
        formoverlay.hide();
    });

    setInterval(() => {
        formoverlay.show();
        let data = $(`input[name="surveyAnalytic"]`).data();
        $.get(`${baseURL}surveys/results`, data).then((response) => {
            formoverlay.hide();
            if(response.code == 200) {
                surveyResults = response.data.result;
                populate_statistics(surveyResults, activeQuestion);
            }
        }).fail((err) => {
            formoverlay.hide();
        });
    }, 300000);
    
    $(`select[name="question_id"][id="question_id"]`).on("change", function() {
        let value = $(this).val();
        activeQuestion = parseInt(value);
        populate_statistics("default", value);
    });

}

click_handler();