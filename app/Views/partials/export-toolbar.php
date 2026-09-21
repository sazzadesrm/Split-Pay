<?php
/**
 * Reusable export toolbar for any section.
 * Vars: $csvUrl (string|null), $exportTarget (CSS selector), $exportName (string)
 */
?>
<div class="export-toolbar no-print" data-export-toolbar data-export-target="<?= e($exportTarget ?? '.export-section') ?>" data-export-name="<?= e($exportName ?? 'split-pay-export') ?>" <?= isset($csvUrl) ? 'data-csv-url="' . e($csvUrl) . '"' : '' ?>>
  <?php if (isset($csvUrl)): ?>
    <button type="button" class="btn btn-outline-secondary btn-sm" data-export-type="csv"><i class="bi bi-filetype-csv"></i> CSV</button>
  <?php endif; ?>
  <button type="button" class="btn btn-outline-secondary btn-sm" data-export-type="pdf"><i class="bi bi-filetype-pdf"></i> PDF</button>
  <button type="button" class="btn btn-outline-secondary btn-sm" data-export-type="png"><i class="bi bi-filetype-png"></i> PNG</button>
  <button type="button" class="btn btn-outline-secondary btn-sm" data-export-type="jpg"><i class="bi bi-filetype-jpg"></i> JPG</button>
  <button type="button" class="btn btn-outline-secondary btn-sm" data-export-type="print"><i class="bi bi-printer"></i> Print</button>
</div>
