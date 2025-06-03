<?php
/**
 * Simplified TCPDF Implementation for Receipt Gallery
 * Basic PDF generation for receipt reports
 */

// PDF Configuration Constants
define('PDF_PAGE_ORIENTATION', 'P');
define('PDF_UNIT', 'mm');
define('PDF_PAGE_FORMAT', 'A4');
define('PDF_MARGIN_LEFT', 15);
define('PDF_MARGIN_TOP', 27);
define('PDF_MARGIN_RIGHT', 15);
define('PDF_MARGIN_HEADER', 5);
define('PDF_MARGIN_BOTTOM', 25);
define('PDF_IMAGE_SCALE_RATIO', 1.25);
define('PDF_FONT_NAME_MAIN', 'helvetica');
define('PDF_FONT_SIZE_MAIN', 10);
define('PDF_FONT_MONOSPACED', 'courier');

class TCPDF {
    private $orientation;
    private $unit;
    private $format;
    private $unicode;
    private $encoding;
    private $diskcache;
    
    private $title = '';
    private $creator = '';
    private $header_title = '';
    private $header_string = '';
    private $header_logo = '';
    private $header_logo_width = 0;
    
    private $pages = array();
    private $current_page = -1;
    private $margins = array();
    private $auto_page_break = true;
    private $break_margin = 0;
    
    private $font_family = 'Arial';
    private $font_size = 12;
    private $font_style = '';
    
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false) {
        $this->orientation = $orientation;
        $this->unit = $unit;
        $this->format = $format;
        $this->unicode = $unicode;
        $this->encoding = $encoding;
        $this->diskcache = $diskcache;
        
        // Set default margins
        $this->margins = array(
            'left' => 15,
            'top' => 27,
            'right' => 15,
            'header' => 5,
            'bottom' => 25
        );
    }
    
    public function SetCreator($creator) {
        $this->creator = $creator;
    }
    
    public function SetTitle($title) {
        $this->title = $title;
    }
    
    public function SetHeaderData($logo = '', $logo_width = 0, $title = '', $string = '') {
        $this->header_logo = $logo;
        $this->header_logo_width = $logo_width;
        $this->header_title = $title;
        $this->header_string = $string;
    }
    
    public function setHeaderFont($font) {
        // Implementation for header font
    }
    
    public function SetDefaultMonospacedFont($font) {
        // Implementation for monospaced font
    }
    
    public function SetMargins($left, $top, $right) {
        $this->margins['left'] = $left;
        $this->margins['top'] = $top;
        $this->margins['right'] = $right;
    }
    
    public function SetHeaderMargin($hm) {
        $this->margins['header'] = $hm;
    }
    
    public function SetAutoPageBreak($auto, $margin = 0) {
        $this->auto_page_break = $auto;
        $this->break_margin = $margin;
    }
    
    public function setImageScale($scale) {
        // Implementation for image scale
    }
    
    public function AddPage($orientation = '', $format = '') {
        $this->current_page++;
        $this->pages[$this->current_page] = array(
            'content' => '',
            'orientation' => $orientation ?: $this->orientation,
            'format' => $format ?: $this->format
        );
    }
    
    public function writeHTML($html, $ln = true, $fill = false, $reseth = false, $cell = false, $align = '') {
        if ($this->current_page < 0) {
            $this->AddPage();
        }
        
        // Simple HTML to text conversion for basic formatting
        $content = $this->convertHTMLToText($html);
        $this->pages[$this->current_page]['content'] .= $content . "\n";
    }
    
    private function convertHTMLToText($html) {
        // Remove HTML tags and convert basic formatting
        $html = strip_tags($html, '<br><p><h1><h2><h3><strong><b><table><tr><td><th><hr>');
        
        // Convert common HTML entities
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        
        // Basic formatting conversions
        $html = str_replace('<br>', "\n", $html);
        $html = str_replace('<hr>', "\n" . str_repeat('-', 50) . "\n", $html);
        $html = preg_replace('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', "\n$1\n" . str_repeat('=', 30) . "\n", $html);
        $html = preg_replace('/<p[^>]*>(.*?)<\/p>/i', "\n$1\n", $html);
        $html = preg_replace('/<(strong|b)[^>]*>(.*?)<\/(strong|b)>/i', "**$2**", $html);
        
        // Table handling (basic)
        $html = preg_replace('/<table[^>]*>/i', "\n", $html);
        $html = preg_replace('/<\/table>/i', "\n", $html);
        $html = preg_replace('/<tr[^>]*>/i', "", $html);
        $html = preg_replace('/<\/tr>/i', "\n", $html);
        $html = preg_replace('/<(td|th)[^>]*>(.*?)<\/(td|th)>/i', "$2\t", $html);
        
        return $html;
    }
    
    public function Output($name = 'doc.pdf', $dest = 'I') {
        // Generate simple text-based PDF content
        $content = $this->generatePDFContent();
        
        switch ($dest) {
            case 'D': // Download
                $this->downloadPDF($content, $name);
                break;
            case 'F': // Save to file
                file_put_contents($name, $content);
                break;
            case 'S': // Return as string
                return $content;
            case 'I': // Send to browser
            default:
                $this->outputToBrowser($content, $name);
                break;
        }
    }
    
    private function generatePDFContent() {
        // Create a simple PDF structure
        $pdf_content = "%PDF-1.4\n";
        $pdf_content .= "1 0 obj\n";
        $pdf_content .= "<<\n";
        $pdf_content .= "/Type /Catalog\n";
        $pdf_content .= "/Pages 2 0 R\n";
        $pdf_content .= ">>\n";
        $pdf_content .= "endobj\n\n";
        
        // Pages object
        $pdf_content .= "2 0 obj\n";
        $pdf_content .= "<<\n";
        $pdf_content .= "/Type /Pages\n";
        $pdf_content .= "/Kids [3 0 R]\n";
        $pdf_content .= "/Count 1\n";
        $pdf_content .= ">>\n";
        $pdf_content .= "endobj\n\n";
        
        // Page object
        $pdf_content .= "3 0 obj\n";
        $pdf_content .= "<<\n";
        $pdf_content .= "/Type /Page\n";
        $pdf_content .= "/Parent 2 0 R\n";
        $pdf_content .= "/MediaBox [0 0 612 792]\n";
        $pdf_content .= "/Contents 4 0 R\n";
        $pdf_content .= "/Resources <<\n";
        $pdf_content .= "/Font <<\n";
        $pdf_content .= "/F1 5 0 R\n";
        $pdf_content .= ">>\n";
        $pdf_content .= ">>\n";
        $pdf_content .= ">>\n";
        $pdf_content .= "endobj\n\n";
        
        // Content stream
        $content_stream = $this->generateContentStream();
        $pdf_content .= "4 0 obj\n";
        $pdf_content .= "<<\n";
        $pdf_content .= "/Length " . strlen($content_stream) . "\n";
        $pdf_content .= ">>\n";
        $pdf_content .= "stream\n";
        $pdf_content .= $content_stream;
        $pdf_content .= "\nendstream\n";
        $pdf_content .= "endobj\n\n";
        
        // Font object
        $pdf_content .= "5 0 obj\n";
        $pdf_content .= "<<\n";
        $pdf_content .= "/Type /Font\n";
        $pdf_content .= "/Subtype /Type1\n";
        $pdf_content .= "/BaseFont /Helvetica\n";
        $pdf_content .= ">>\n";
        $pdf_content .= "endobj\n\n";
        
        // Cross-reference table
        $pdf_content .= "xref\n";
        $pdf_content .= "0 6\n";
        $pdf_content .= "0000000000 65535 f \n";
        $pdf_content .= "0000000010 65535 n \n";
        $pdf_content .= "0000000079 65535 n \n";
        $pdf_content .= "0000000173 65535 n \n";
        $pdf_content .= "0000000301 65535 n \n";
        $pdf_content .= "0000000380 65535 n \n";
        
        // Trailer
        $pdf_content .= "trailer\n";
        $pdf_content .= "<<\n";
        $pdf_content .= "/Size 6\n";
        $pdf_content .= "/Root 1 0 R\n";
        $pdf_content .= ">>\n";
        $pdf_content .= "startxref\n";
        $pdf_content .= "492\n";
        $pdf_content .= "%%EOF\n";
        
        return $pdf_content;
    }
    
    private function generateContentStream() {
        $stream = "BT\n";
        $stream .= "/F1 12 Tf\n";
        $stream .= "50 750 Td\n";
        
        // Add header
        if ($this->header_title) {
            $stream .= "(" . $this->escapeString($this->header_title) . ") Tj\n";
            $stream .= "0 -20 Td\n";
        }
        
        if ($this->header_string) {
            $stream .= "(" . $this->escapeString($this->header_string) . ") Tj\n";
            $stream .= "0 -30 Td\n";
        }
        
        // Add content from pages
        foreach ($this->pages as $page) {
            $lines = explode("\n", $page['content']);
            foreach ($lines as $line) {
                if (trim($line)) {
                    $stream .= "(" . $this->escapeString(trim($line)) . ") Tj\n";
                }
                $stream .= "0 -15 Td\n";
            }
        }
        
        $stream .= "ET\n";
        return $stream;
    }
    
    private function escapeString($string) {
        // Escape special PDF characters
        $string = str_replace('\\', '\\\\', $string);
        $string = str_replace('(', '\\(', $string);
        $string = str_replace(')', '\\)', $string);
        return $string;
    }
    
    private function downloadPDF($content, $filename) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $content;
    }
    
    private function outputToBrowser($content, $filename) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
    }
}

// Alternative: Use built-in PHP to generate simple receipt text file if PDF is too complex
class SimpleReceiptGenerator {
    public static function generateTextReceipt($receipts_data, $start_date, $end_date) {
        $content = "DAILY RECEIPTS REPORT\n";
        $content .= "=====================\n";
        $content .= "Period: " . date('d/m/Y', strtotime($start_date)) . " to " . date('d/m/Y', strtotime($end_date)) . "\n";
        $content .= "Generated: " . date('d/m/Y H:i:s') . "\n\n";
        
        $current_trans = '';
        $total_amount = 0;
        
        foreach ($receipts_data as $row) {
            if ($current_trans != $row['trans_id']) {
                if ($current_trans != '') {
                    $content .= str_repeat('-', 50) . "\n\n";
                }
                
                $content .= "RECEIPT #" . $row['trans_id'] . "\n";
                $content .= "Date: " . date('d/m/Y', strtotime($row['trans_date'])) . "\n";
                $content .= "Time: " . date('h:i A', strtotime($row['trans_time'])) . "\n";
                $content .= str_repeat('-', 30) . "\n";
                
                $current_trans = $row['trans_id'];
                $total_amount = $row['total_amount'];
            }
            
            if ($row['menu_name']) {
                $item_total = $row['quantity'] * $row['unit_price'];
                $content .= sprintf("%-20s %2d x %6.2f = %7.2f\n", 
                    substr($row['menu_name'], 0, 20), 
                    $row['quantity'], 
                    $row['unit_price'], 
                    $item_total
                );
            }
        }
        
        if (!empty($receipts_data)) {
            $content .= str_repeat('-', 30) . "\n";
            $content .= sprintf("TOTAL: RM %7.2f\n", $total_amount);
            $content .= str_repeat('=', 50) . "\n";
        }
        
        return $content;
    }
    
    public static function downloadTextFile($content, $filename) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . str_replace('.pdf', '.txt', $filename) . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
    }
}

?>