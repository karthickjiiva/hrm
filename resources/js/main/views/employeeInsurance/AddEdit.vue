<template>
  <a-drawer
    :visible="visible"
    :title="addEditType === 'add' ? 'Add Insurance Details' : 'Edit Insurance Details'"
    width="900"
    @close="onClose"
    destroy-on-close
  >
    <a-form ref="formRef" :model="formData" :rules="rules" layout="vertical">
      <a-tabs v-model:activeKey="activeTab" :destroyInactiveTabPane="false">
        
        <a-tab-pane key="1" tab="Employee & Spouse" :forceRender="true">
          <a-row :gutter="16">
            <a-col :span="12">
              <a-form-item label="Select Employee" name="user_id">
<a-select
  v-model:value="formData.user_id"
  show-search
  option-filter-prop="label"
  placeholder="Search employee"
  :options="employeeOptions"
  :loading="fetchingEmployees"
  @change="onEmployeeChange"
/>              </a-form-item>
            </a-col>
            <a-col :span="12">
              <a-form-item label="Father Name" name="father_name">
                <a-input v-model:value="formData.father_name" />
              </a-form-item>
            </a-col>
            <a-col :span="12">
              <a-form-item label="Spouse Name" name="spouse_name">
                <a-input v-model:value="formData.spouse_name" />
              </a-form-item>
            </a-col>
            <a-col :span="12">
              <a-form-item label="Spouse DOB" name="spouse_dob">
                <a-date-picker v-model:value="formData.spouse_dob" valueFormat="YYYY-MM-DD" style="width:100%" />
              </a-form-item>
            </a-col>
            <a-col :span="12">
              <a-form-item label="Spouse Aadhar" name="spouse_aadhar">
                <a-input 
                  v-model:value="formData.spouse_aadhar" 
                  placeholder="12 digit number" 
                  @input="formData.spouse_aadhar = formData.spouse_aadhar.replace(/\D/g, '')"
                  :maxlength="12"
                />
              </a-form-item>
            </a-col>
          </a-row>
        </a-tab-pane>

        <a-tab-pane key="2" tab="Children" :forceRender="true">
          <a-row :gutter="16">
            <a-col :span="8">
              <a-form-item label="Child 1 Name" name="child1_name">
                <a-input v-model:value="formData.child1_name" />
              </a-form-item>
            </a-col>
            <a-col :span="8">
              <a-form-item label="Child 1 DOB" name="child1_dob">
                <a-date-picker v-model:value="formData.child1_dob" valueFormat="YYYY-MM-DD" style="width:100%" />
              </a-form-item>
            </a-col>
            <a-col :span="8">
              <a-form-item label="Child 1 Aadhar" name="child1_aadhar">
                <a-input 
                  v-model:value="formData.child1_aadhar" 
                  @input="formData.child1_aadhar = formData.child1_aadhar.replace(/\D/g, '')"
                  :maxlength="12"
                />
              </a-form-item>
            </a-col>
            <a-col :span="8">
              <a-form-item label="Child 2 Name" name="child2_name">
                <a-input v-model:value="formData.child2_name" />
              </a-form-item>
            </a-col>
            <a-col :span="8">
              <a-form-item label="Child 2 DOB" name="child2_dob">
                <a-date-picker v-model:value="formData.child2_dob" valueFormat="YYYY-MM-DD" style="width:100%" />
              </a-form-item>
            </a-col>
            <a-col :span="8">
              <a-form-item label="Child 2 Aadhar" name="child2_aadhar">
                <a-input 
                  v-model:value="formData.child2_aadhar" 
                  @input="formData.child2_aadhar = formData.child2_aadhar.replace(/\D/g, '')"
                  :maxlength="12"
                />
              </a-form-item>
            </a-col>
          </a-row>
        </a-tab-pane>

        <a-tab-pane key="3" tab="Address & Nominee" :forceRender="true">
          <a-row :gutter="16">
            <a-col :span="12">
              <a-form-item label="Permanent Address" name="permanent_address">
                <a-textarea rows="3" v-model:value="formData.permanent_address" />
              </a-form-item>
            </a-col>
          <a-col :span="12" class="comm-address-wrapper">
  <a-form-item
    name="communication_address"
    label="Communication Address"
    class="comm-address-item"
  >
    <a-textarea
      rows="3"
      v-model:value="formData.communication_address"
      :disabled="sameAddress"
    />
  </a-form-item>

  <div class="comm-switch">
    <span>Same as permanent</span>
    <a-switch size="small" v-model:checked="sameAddress" />
  </div>
</a-col>
            <a-col :span="12">
              <a-form-item label="Nominee Name" name="nominee_name">
                <a-input v-model:value="formData.nominee_name" />
              </a-form-item>
            </a-col>
            <a-col :span="12">
              <a-form-item label="Nominee Relation" name="nominee_relation">
                <a-input v-model:value="formData.nominee_relation" />
              </a-form-item>
            </a-col>
          </a-row>
        </a-tab-pane>
      </a-tabs>

      <div style="text-align:right;margin-top:20px">
        <a-button style="margin-right:8px" @click="onClose" :disabled="submitting">Cancel</a-button>
        <a-button type="primary" :loading="submitting" @click="submitForm">Submit</a-button>
      </div>
    </a-form>
  </a-drawer>
</template>

<script>
import { ref, watch, onMounted } from "vue";
import axios from "axios";
import { message } from 'ant-design-vue';

export default {
  props: ["visible", "addEditType", "formData", "data"],
  emits: ["closed", "addEditSuccess"],
  setup(props, { emit }) {
    const formRef = ref(null);
    const activeTab = ref("1");
    const fetchingEmployees = ref(false);
    const submitting = ref(false);
    const employeeOptions = ref([]);
    const sameAddress = ref(false);

    // Watcher to sync addresses
    watch(sameAddress, (val) => {
      if (val) {
        props.formData.communication_address = props.formData.permanent_address;
      }
    });

    // Real-time sync if permanent address changes while switch is ON
    watch(() => props.formData.permanent_address, (newVal) => {
       if (sameAddress.value) props.formData.communication_address = newVal;
    });

    const aadharRule = [
      { required: true, message: 'Aadhar is required' },
      { len: 12, message: 'Must be 12 digits' }
    ];

    const rules = {
      user_id: [{ required: true, message: 'Required' }],
      father_name: [{ required: true, message: 'Required' }],
      spouse_name: [{ required: true, message: 'Required' }],
      spouse_dob: [{ required: true, message: 'Required' }],
      spouse_aadhar: aadharRule,
      child1_name: [{ required: true, message: 'Required' }],
      child1_dob: [{ required: true, message: 'Required' }],
      child1_aadhar: aadharRule,
      child2_name: [{ required: true, message: 'Required' }],
      child2_dob: [{ required: true, message: 'Required' }],
      child2_aadhar: aadharRule,
      permanent_address: [{ required: true, message: 'Required' }],
      communication_address: [{ required: true, message: 'Required' }],
      nominee_name: [{ required: true, message: 'Required' }],
      nominee_relation: [{ required: true, message: 'Required' }],
    };

    const submitForm = async () => {
      try {
        await formRef.value.validate();
        submitting.value = true;
        await axios.post("/api/v1/add-emp-insurances", props.formData);
        message.success("Saved successfully");
        emit("addEditSuccess");
        emit("closed");
      } catch (err) {
        if (err.errorFields) {
          const tabMap = {
            1: ['user_id','father_name','spouse_name','spouse_dob','spouse_aadhar'],
            2: ['child1_name','child1_dob','child1_aadhar','child2_name','child2_dob','child2_aadhar'],
            3: ['permanent_address','communication_address','nominee_name','nominee_relation']
          };
          const firstErrorField = err.errorFields[0].name[0];
          for (const [tab, fields] of Object.entries(tabMap)) {
            if (fields.includes(firstErrorField)) {
              activeTab.value = tab;
              break;
            }
          }
          message.warning("Please check required fields");
        }
      } finally {
        submitting.value = false;
      }
    };

    const fetchEmployees = async () => {
      fetchingEmployees.value = true;
      try {
        const res = await axios.get("/api/v1/employees/all");
        employeeOptions.value = res.data.data.map(emp => ({ label: emp.name, value: emp.id }));
      } finally {
        fetchingEmployees.value = false;
      }
    };

    const onEmployeeChange = async (id) => {
      const res = await axios.get(`/api/v1/employeesinfo/${id}`);
      props.formData.spouse_name = res.data.data.spouse_name;
      props.formData.permanent_address = res.data.data.permanent_address;
    };

    onMounted(fetchEmployees);

    return { 
      formRef, rules, activeTab, employeeOptions, fetchingEmployees, 
      submitting, sameAddress, submitForm, onEmployeeChange, 
      onClose: () => emit("closed") 
    };
  }
};
</script>

<style scoped>
.comm-address-wrapper {
  position: relative;
}

.comm-switch {
  position: absolute;
  top: 6px;       
  right: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
}
</style>