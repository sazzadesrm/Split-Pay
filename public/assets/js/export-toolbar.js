/**
 * Generic export toolbar: adds CSV, PDF, PNG and JPG export to any section
 * marked up with data-export-toolbar. CSV downloads from the server
 * (data-csv-url, honoring the section's active filters and authorization).
 * PDF/PNG/JPG are captured client-side from the target element via
 * html2canvas + jsPDF, so they work on any page without new server code.
 */
(function () {
  'use strict';

  function captureCanvas(target) {
    return html2canvas(target, { backgroundColor: '#ffffff', scale: 2, useCORS: true });
  }

  function downloadDataUrl(dataUrl, filename) {
    const a = document.createElement('a');
    a.href = dataUrl;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
  }

  async function exportImage(target, filename, mime) {
    const canvas = await captureCanvas(target);
    downloadDataUrl(canvas.toDataURL(mime, 0.95), filename);
  }

  async function exportPdf(target, filename) {
    const canvas = await captureCanvas(target);
    const { jsPDF } = window.jspdf;
    const orientation = canvas.width > canvas.height ? 'l' : 'p';
    const pdf = new jsPDF(orientation, 'pt', [canvas.width, canvas.height]);
    pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, canvas.width, canvas.height);
    pdf.save(filename);
  }

  function toggleBusy(toolbar, busy) {
    toolbar.querySelectorAll('button').forEach((b) => { b.disabled = busy; });
  }

  document.querySelectorAll('[data-export-toolbar]').forEach(function (toolbar) {
    const targetSelector = toolbar.getAttribute('data-export-target');
    const target = targetSelector ? document.querySelector(targetSelector) : toolbar.closest('.export-section');
    const baseName = toolbar.getAttribute('data-export-name') || 'split-pay-export';

    toolbar.querySelectorAll('[data-export-type]').forEach(function (btn) {
      btn.addEventListener('click', async function () {
        const type = btn.getAttribute('data-export-type');
        if (type === 'csv') {
          const csvUrl = toolbar.getAttribute('data-csv-url');
          if (csvUrl) {
            window.location.href = csvUrl;
          }
          return;
        }
        if (!target) return;
        toggleBusy(toolbar, true);
        try {
          if (type === 'pdf') {
            await exportPdf(target, baseName + '.pdf');
          } else if (type === 'png') {
            await exportImage(target, baseName + '.png', 'image/png');
          } else if (type === 'jpg') {
            await exportImage(target, baseName + '.jpg', 'image/jpeg');
          } else if (type === 'print') {
            window.print();
          }
        } catch (e) {
          console.error('Export failed', e);
          window.alert('Export failed. Please try again.');
        } finally {
          toggleBusy(toolbar, false);
        }
      });
    });
  });
})();
