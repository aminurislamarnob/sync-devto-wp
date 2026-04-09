(function ($) {
  "use strict";

  var $importBtn, $spinner, $resultsWrap, $resultsSummary, $messagesLog;

  function init() {
    $importBtn = $("#sdwp-import-btn");
    $spinner = $("#sdwp-spinner");
    $resultsWrap = $("#sdwp-results");
    $resultsSummary = $("#sdwp-results-summary");
    $messagesLog = $("#sdwp-messages-log");

    $importBtn.on("click", handleImport);
  }

  function handleImport(e) {
    e.preventDefault();

    if (!confirm(sdwpAdmin.i18n.confirm)) {
      return;
    }

    setImporting(true);

    $.ajax({
      url: sdwpAdmin.ajaxUrl,
      type: "POST",
      data: {
        action: "sdwp_import_articles",
        nonce: sdwpAdmin.nonce,
      },
      timeout: 600000,
      success: function (response) {
        setImporting(false);

        if (response.success) {
          displayResults(response.data);
        } else {
          displayError(
            response.data && response.data.message
              ? response.data.message
              : sdwpAdmin.i18n.importFailed
          );
        }
      },
      error: function (xhr, status, error) {
        setImporting(false);
        displayError(sdwpAdmin.i18n.importFailed + " (" + status + ")");
      },
    });
  }

  function setImporting(isImporting) {
    $importBtn.prop("disabled", isImporting);
    $spinner.toggleClass("is-active", isImporting);

    if (isImporting) {
      $importBtn.text(sdwpAdmin.i18n.importing);
      $resultsWrap.hide();
    } else {
      $importBtn.text($importBtn.data("label"));
    }
  }

  function displayResults(data) {
    var html =
      '<table class="widefat sdwp-results-table">' +
      "<thead><tr><th>" +
      sdwpAdmin.i18n.importDone +
      "</th><th>#</th></tr></thead>" +
      "<tbody>" +
      '<tr class="sdwp-created"><td>' +
      sdwpAdmin.i18n.created +
      "</td><td><strong>" +
      data.created +
      "</strong></td></tr>" +
      '<tr class="sdwp-updated"><td>' +
      sdwpAdmin.i18n.updated +
      "</td><td><strong>" +
      data.updated +
      "</strong></td></tr>" +
      '<tr class="sdwp-skipped"><td>' +
      sdwpAdmin.i18n.skipped +
      "</td><td><strong>" +
      data.skipped +
      "</strong></td></tr>" +
      '<tr class="sdwp-failed"><td>' +
      sdwpAdmin.i18n.failed +
      "</td><td><strong>" +
      data.failed +
      "</strong></td></tr>" +
      "</tbody></table>";

    $resultsSummary.html(html);

    if (data.messages && data.messages.length > 0) {
      var msgHtml = '<div class="sdwp-messages"><h4>Import Log</h4><ul>';
      for (var i = 0; i < data.messages.length; i++) {
        msgHtml += "<li>" + $("<span>").text(data.messages[i]).html() + "</li>";
      }
      msgHtml += "</ul></div>";
      $messagesLog.html(msgHtml);
    } else {
      $messagesLog.empty();
    }

    $resultsWrap.show();
  }

  function displayError(message) {
    $resultsSummary.html(
      '<div class="notice notice-error inline"><p>' +
        $("<span>").text(message).html() +
        "</p></div>"
    );
    $messagesLog.empty();
    $resultsWrap.show();
  }

  $(document).ready(init);
})(jQuery);
