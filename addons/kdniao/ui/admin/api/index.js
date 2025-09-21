import request from '@/utils/request'

// 查询定时任务调度列表
export function queryKdniao(query) {
	return request({
		url: '/kdniao',
		method: 'get',
		params: query
	})
}