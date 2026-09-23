const assert = require('assert');

const element = () => ({ children: [], dataset: {}, disabled: false, textContent: '', listeners: {}, append(child) { this.children.push(child); }, addEventListener(type, callback) { this.listeners[type] = callback; }, replaceChildren() { this.children = []; } });
const form = element();form.elements={enabled:{checked:true}};const history=element();const status=element();const calls=[];
global.window={BRTop:{api:{request:async(path,options={})=>{calls.push([path,options.method||'GET',options.body||null]);return{history:[]};}}}};
global.document={getElementById:(id)=>({'brtop-full-access-form':form,'brtop-full-access-history':history,'brtop-full-access-status':status}[id]||null),createElement:()=>element()};
global.FormData=class{get(name){return{enabled:'on',targetUid:' admin-target ',durationMinutes:'60'}[name];}};
require('../../js/admin-access.js');

setImmediate(async()=>{await form.listeners.submit({preventDefault(){}});const button={dataset:{revokeUid:'admin-target'},disabled:false};await history.listeners.click({target:{closest:()=>button}});assert.deepStrictEqual(calls.map(([path,method])=>[path,method]),[['/api/admin/full-access','GET'],['/api/admin/full-access','POST'],['/api/admin/full-access','GET'],['/api/admin/full-access/admin-target','DELETE'],['/api/admin/full-access','GET']]);assert(status.textContent.includes('widerrufen'));console.log('BRTop admin access UI smoke test passed.');});
