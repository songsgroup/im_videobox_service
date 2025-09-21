<template>
	<div @click="handleClick">
		<slot />
		<!-- 物流详情弹窗 -->
		<el-dialog :title="traces.title" v-model="traces.open" width="80%" top="5vh" append-to-body class="scrollbar">
			<el-timeline style="max-width: 600px">
				<el-timeline-item v-for="(items, index) in traces.traces" :key="index"
					:color="index?'':'#0bbd87'"
					:timestamp="items.AcceptTime">
					{{ items.AcceptStation }}
				</el-timeline-item>
			</el-timeline>
		</el-dialog>
	</div>
</template>

<script setup>
	import {queryKdniao} from '@/api/addons/kdniao/index';
	const props = defineProps({
		code: {
			type: String,
			default: ''
		},
		company: {
			type: String,
			default: ''
		}
	})
	const traces = ref({
		title: '物流详情',
		open: false,
		traces:[],
	})

	function handleClick() {
		traces.value.title = '物流详情： '+props.code;
		queryKdniao({company:props.company,code:props.code}).then(res=>{
			traces.value.traces = res.data;
			traces.value.open = true;
		})
	}
</script>

<style>
</style>