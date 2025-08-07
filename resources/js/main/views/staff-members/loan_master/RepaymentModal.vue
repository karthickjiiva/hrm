<template>
    <a-modal :visible="visible" title="Manage Loan Payments" :footer="null" @cancel="closeModal" centered width="700px">
        <div>
            <!-- <h3>Repayment Details for Loan #{{ loanXid }}</h3> -->

            <a-table :columns="columns" :data-source="repayments" :loading="loading" row-key="id" size="middle"
                bordered>
                <template #bodyCell="{ column, record }">
                    <template v-if="column.dataIndex === 'status'">
                        <a-tag :color="statusColor(record.status)"
                            style="font-size: 15px;border-radius: 25px;padding: 5px 15px;">
                            {{ record.status }}
                        </a-tag>

                    </template>

                    <template v-if="column.dataIndex === 'action'">
                        <a-button type="primary" danger size="medium" @click="skipMonth(record.id)"
                            style="padding: 0px 20px"
                            :disabled="record.status !== 'pending' || !isCurrentMonth(record.repayment_month)">
                            Skip
                        </a-button>

                    </template>
                </template>
            </a-table>
            <!-- Footer -->
            <div style="text-align: center; margin-top: 0px;">
                <a-tooltip v-if="!hasOutstanding" title="Loan paid">
                    <a-button type="primary" disabled style="margin-right: 8px;">
                        Close Loan
                    </a-button>
                </a-tooltip>

                <a-button v-else type="primary" danger size="medium" @click="handleCloseLoan" style="padding: 0px 20px">
                    Close Loan
                </a-button>
            </div>
        </div>
    </a-modal>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from "axios";
import dayjs from "dayjs";
import { message, Modal } from 'ant-design-vue';
import { computed } from 'vue';

const props = defineProps({
    visible: Boolean,
    loanXid: String,
});

const emit = defineEmits(['update:visible', 'updated']);

const repayments = ref([]);
const loading = ref(false);

const hasOutstanding = computed(() => {
    return repayments.value.some(r => r.status === 'pending');
});

const columns = [
    {
        title: 'Month',
        dataIndex: 'repayment_month',
        key: 'repayment_month',
        customRender: ({ text }) => {
            const date = new Date(text);
            return date.toLocaleString('default', { month: 'short', year: 'numeric' });
        }
    },
    {
        title: 'Amount',
        dataIndex: 'amount',
        key: 'amount',
    },
    {
        title: 'Status',
        dataIndex: 'status',
        key: 'status',
    },
    {
        title: 'Action',
        dataIndex: 'action',
        key: 'action',
    },
];


function closeModal() {
    emit('update:visible', false);
}

async function handleCloseLoan() {
    const pendingRepayments = repayments.value.filter(r => r.status === 'pending');
    const outstandingAmount = pendingRepayments.reduce((sum, r) => sum + parseFloat(r.amount), 0);

    if (pendingRepayments.length > 0) {
        Modal.confirm({
            title: `Loan has outstanding amount of ₹${outstandingAmount.toFixed(2)}`,
            content: 'Do you still want to force close this loan?',
            okText: 'Force Close',
            cancelText: 'Cancel',
            onOk: async () => {
                await closeLoan(true);
            },
        });
    } else {
        Modal.confirm({
            title: 'Are you sure you want to close this loan?',
            okText: 'Yes',
            cancelText: 'No',
            onOk: async () => {
                await closeLoan(false);
            },
        });
    }
}

async function closeLoan(force = false) {
    try {
        await axios.post(`/api/v1/loan_repayments/${props.loanXid}/close`, {
            force,
        });
        message.success('Loan closed successfully');
        emit('updated');
        emit('update:visible', false);
    } catch (err) {
        console.error('Failed to close loan', err);
        message.error('Failed to close loan');
    }
}


function isCurrentMonth(month) {
    const current = dayjs().format('YYYY-MM');
    return dayjs(month).format('YYYY-MM') === current;
}

function statusColor(status) {
    switch (status) {
        case 'paid':
            return 'green';
        case 'pending':
            return 'orange';
        case 'skipped':
            return 'red';
        default:
            return 'gray';
    }
}

async function fetchRepayments() {
    if (!props.loanXid) return;
    loading.value = true;
    try {
        const response = await axios.get(`/api/v1/loan_repayments/${props.loanXid}`);
        repayments.value = response.data.data;
    } catch (err) {
        console.error('Failed to fetch repayments', err);
    } finally {
        loading.value = false;
    }
}

async function skipMonth(repaymentId) {
    try {
        await axios.post(`/api/v1/loan_repayments/${repaymentId}/skip`);
        await fetchRepayments();
        emit('updated');
    } catch (err) {
        console.error('Failed to skip month', err);
    }
}

watch(
    () => props.visible,
    (newVal) => {
        if (newVal) {
            fetchRepayments();
        }
    }
);
</script>

<style scoped>
h3 {
    margin-bottom: 1rem;
}
</style>
