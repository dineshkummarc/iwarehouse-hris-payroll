<!DOCTYPE html>
<html>
    <head>
        <title>EMPLOYEE PLAYSLIP</title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0"/>
        <style type="text/css" media="print">
            @media all{
                p {font-size: 60%; margin: 0 0 0 0; padding: 0 0 0 0;}
            }
            @media print{
                .noprint, .noprint * {display:none !important; height: 0;}
                .pgsize {height: 960px;}
                body { background:#FFF; }
                @page {
                    size: 8.5in 11in;
                }
                .w3-orange{color:#000!important;background-color:#f78902!important}
            }
            .pcont { page-break-inside : avoid;  }
            .breakpoint { page-break-after: always; }
        </style>
    </head>
    <body>
        <?php echo $payslip_report;  ?>
    </body>
</html>
<script>
    window.onload = function () {
        window.print();
    };
</script> 