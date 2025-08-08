<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
        /* General Body Styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 15px;
        }

        /* Layout & Spacing */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td, th {
            padding: 4px 0;
            vertical-align: top;
        }
        .container {
            padding: 10px;
        }
        .section-break {
            border: none;
            border-top: 1px solid #c7c7c7;
            margin: 15px 0;
        }

        /* Header */
         .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px; /* Space between name/logo and address */
        }

        .header-table td {
            padding: 0; /* Reset padding for this specific table */
            vertical-align: middle; /* Vertically aligns the name and logo */
        }

        .company-name {
            font-weight: bold;
            font-size: 13px;
            text-align: left;
        }

        .company-address {
            font-size: 12px;
            margin: 0;
        }

        .logo-cell {
            text-align: right;
        }

        .company-logo {
            width: 150px; /* Adjust width as needed */
            height: auto;
            display: block;
        }
        /* Employee Details Section */
        .employee-name {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .employee-details-table td {
            width: 25%;
            padding-bottom: 10px;
        }
        .employee-details-table .label {
            font-weight: bold;
            display: block;
            margin-bottom: 2px;
            color: #555;
        }

        /* Salary Details (Payable Days) */
        .salary-details-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 5px;
        }
        .salary-details-table td {
            text-align: left;
        }
        .salary-details-table .label {
            font-weight: bold;
            display: block;
            margin-bottom: 2px;
            color: #555;
        }
        
        /* Main Content: Earnings, Contributions, Deductions */
        .main-content-table > tbody > tr > td {
           vertical-align: top;
           padding-right: 20px;
        }
        .main-content-table > tbody > tr > td:last-child {
            padding-right: 0;
        }
        
        .data-table th {
            text-align: left;
            font-weight: bold;
            padding-bottom: 8px;
            border-bottom: 1px solid #c7c7c7;
        }
        .data-table td {
            padding: 5px 0;
        }
        .data-table .amount {
            text-align: right;
        }
        .data-table .total-row td {
            font-weight: bold;
            padding-top: 8px;
        }
        .contributions-deductions-table {
            margin-bottom: 15px; /* Space between Contributions and Taxes */
        }

        /* Net Salary Section */
        .net-salary-table {
            margin-top: 10px;
        }
        .net-salary-table .label {
            font-weight: bold;
        }
        .net-salary-table .total-amount {
            font-weight: bold;
            text-align: right;
            font-size: 13px;
        }
        .net-salary-words {
            font-weight: bold;
            margin-top: 5px;
        }
        
        /* Footer */
        .note {
            font-size: 10px;
            margin-top: 15px;
        }
        .footer {
            font-size: 10px;
            text-align: center;
            margin-top: 20px;
            font-style: italic;
            color: #888;
        }
         .payslip-title {
            font-size: 16px;
            margin: 0 0 5px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="container">
   
         <h1 class="payslip-title">PAYSLIP {{ strtoupper(\Carbon\Carbon::create()->month($payroll->month)->format('M')) }} {{ $payroll->year }}</h1>

        <table class="header-table">
            <tr>
                <td class="company-name">BUILDCRAFT INTERIOR PVT LTD</td>
                <td class="logo-cell">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" class="company-logo" alt="Company Logo"/>
                    @else
                        <span>[Logo not found]</span>
                    @endif
                </td>
            </tr>
        </table>
        
        <!-- Company Address below the name/logo line -->
        <p class="company-address">
            NO.2, 8TH FLOOR, KRM CENTER, HARRINGTON ROAD, CHETPET, CHENNAI - 600031<br>
            CHENNAI TAMILNADU 600031
        </p>

        <hr class="section-break">

        <div class="employee-name">{{ $payroll->employee->name ?? 'N/A' }}</div>
        <table class="employee-details-table">
            <tr>
                <td>
                    <span class="label">Employee Number</span>
                    {{ $payroll->employee->employee_number ?? 'N/A' }}
                </td>
                <td>
                    <span class="label">Date Joined</span>
                    {{ optional($payroll->employee->joining_date)->format('d M Y') ?? 'N/A' }}
                </td>
                <td>
                    <span class="label">Department</span>
                    {{ optional($payroll->employee->department)->name ?? 'PROJECT' }}
                </td>
                <td>
                    <span class="label">Sub Department</span>
                    N/A
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Designation</span>
                    {{ optional($payroll->employee->designation)->name ?? 'N/A' }}
                </td>
                <td>
                    <span class="label">Payment Mode</span>
                    Bank Transfer
                </td>
                <td>
                    <span class="label">Bank</span>
                    {{-- Assuming bank name is stored on employee, adjust if needed --}}
                    {{ $payroll->employee->bank_name ?? 'HDFC Bank' }}
                </td>
                <td>
                    <span class="label">Bank IFSC</span>
                    {{ $payroll->employee->ifsc_code ?? 'HDFC0000444' }}
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Bank Account</span>
                    {{ $payroll->employee->account_number ?? '50100739571816' }}
                </td>
                <td>
                    <span class="label">UAN</span>
                    {{ $payroll->employee->uan_number ?? 'N/A' }}
                </td>
                <td>
                    <span class="label">PF Number</span>
                    {{ $payroll->employee->pf_number ?? 'N/A' }}
                </td>
                <td>
                    <span class="label">ESI Number</span>
                    {{ $payroll->employee->esi_number ?? 'N/A' }}
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <span class="label">PAN Number</span>
                    {{ $payroll->employee->pan_number ?? 'N/A' }}
                </td>
            </tr>
        </table>
        
        <hr class="section-break">

        <div class="salary-details-title">SALARY DETAILS</div>
        <table class="salary-details-table">
            <tr>
                <td><span class="label">Total Working Days</span> {{$payroll->total_working_days }} Days</td>
                <td><span class="label">Actual Payable Days</span> {{ $payroll->actual_payable_days, 1 }} Days</td>                
                <td><span class="label">Loss Of Pay Days</span> {{ $payroll->loss_of_pay_days, 1 }} Days</td>
                <td><span class="label">Days Payable</span> {{ $payroll->days_payable }}</td>
            </tr>
        </table>

        <hr class="section-break">

        <table class="main-content-table">
            <tr>
                <td style="width: 50%;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th colspan="2">EARNINGS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Basic</td>
                                <td class="amount">{{ number_format($payroll->basic, 2) }}</td>
                            </tr>
                            <tr>
                                <td>HRA</td>
                                <td class="amount">{{ number_format($payroll->hra, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Conveyance Allowance</td>
                                <td class="amount">{{ number_format($payroll->allowance, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Food Allowance</td>
                                <td class="amount">{{ number_format($payroll->food_allowance, 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td>Total Earnings (A)</td>
                                <td class="amount">{{ number_format($payroll->total_earnings, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </td>

                <td style="width: 50%;">
                    <table class="data-table contributions-deductions-table">
                        <thead>
                            <tr>
                                <th colspan="2">CONTRIBUTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>PF Employee</td>
                                <td class="amount">{{ number_format($payroll->pf_employee, 2) }}</td>
                            </tr>
                            <tr>
                                <td>ESI Employee</td>
                                <td class="amount">{{ number_format($payroll->esi_employee, 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                             <tr class="total-row">
                                <td>Total Contributions (B)</td>
                                <td class="amount">{{ number_format($payroll->total_contributions, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>

                    <table class="data-table contributions-deductions-table">
                        <thead>
                            <tr>
                                <th colspan="2">TAXES & DEDUCTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Professional Tax</td>
                                <td class="amount">{{ number_format($payroll->professional_tax, 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                             <tr class="total-row">
                                <td>Total Taxes & Deductions (C)</td>
                                <td class="amount">{{ number_format($payroll->total_taxes_deductions, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>

                       <table class="data-table contributions-deductions-table">
                        <thead>
                            <tr>
                                <th colspan="2">LOAN & ADVANCE </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>LOAN</td>
                                <td class="amount">{{ number_format($payroll->professional_tax, 2) }}</td>
                            </tr>
                              <tr>
                                <td>ADVANCE</td>
                                <td class="amount">{{ number_format($payroll->professional_tax, 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                             <tr class="total-row">
                                <td>Total LOAN & ADVANCE (D)</td>
                                <td class="amount">{{ number_format($payroll->total_taxes_deductions, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </td>
            </tr>
        </table>

        <hr class="section-break">

        <table class="net-salary-table">
            <tr>
                <td class="label">Net Salary Payable (A - B - C)</td>
                <td class="total-amount">{{ number_format($payroll->net_salary, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Net Salary in words</td>
                <td class="total-amount">{{ $payroll->net_salary_in_words }}</td>
            </tr>
        </table>
        
        <p class="note"><strong>**Note :</strong> All amounts displayed in this payslip are in INR</p>

        <div class="footer">
            * This is computer generated statement, does not require signature.
        </div>

    </div>
</body>
</html>