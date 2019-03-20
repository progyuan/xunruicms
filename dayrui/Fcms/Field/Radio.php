<?php namespace Phpcmf\Field;

class Radio extends \Phpcmf\Library\A_Field {
	
	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
		$this->fieldtype = TRUE; // TRUE表全部可用字段类型,自定义格式为 array('可用字段类型名称' => '默认长度', ... )
		$this->defaulttype = 'VARCHAR'; // 当用户没有选择字段类型时的缺省值
    }
	
	/**
	 * 字段相关属性参数
	 *
	 * @param	array	$value	值
	 * @return  string
	 */
	public function option($option) {

		$option['options'] = isset($option['options']) ? $option['options'] : 'name1|value1'.PHP_EOL.'name2|value2';

		return [
			'
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('选项列表').'</label>
				<div class="col-md-9">
					<textarea class="form-control" name="data[setting][option][options]" style="height:150px;width:400px;">'.$option['options'].'</textarea>
					<span class="help-block">'.dr_lang('格式：选项名称|选项值[回车换行]选项名称2|值2....').'</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('默认选中项').'</label>
				<div class="col-md-9">
					<label><input id="field_default_value" type="text" class="form-control" size="20" value="'.$option['value'].'" name="data[setting][option][value]"></label>
					<label>'.$this->member_field_select().'</label>
					<span class="help-block">'.dr_lang('默认选中项，多个选中项用|分隔').'</span>
				</div>
			</div>'.$this->field_type($option['fieldtype'], $option['fieldlength']).'
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('显示格式').'</label>
				<div class="col-md-9">
					<div class="mt-radio-inline">
						<label class="mt-radio  mt-radio-outline">
							<input type="radio" name="data[setting][option][show_type]" value="0" '.(!$option['show_type'] ? 'checked' : '').'> '.dr_lang('横排显示').'
							<span></span>
						</label>
						<label class="mt-radio  mt-radio-outline">
							<input type="radio" name="data[setting][option][show_type]" value="1" '.($option['show_type'] ? 'checked' : '').'> '.dr_lang('竖排显示').'
							<span></span>
						</label>
					</div>
					
				</div>
			</div>
			'
		];
	}

	/**
	 * 字段表单输入
	 *
	 * @return  string
	 */
	public function input($field, $value = '') {

		// 字段禁止修改时就返回显示字符串
		if ($this->_not_edit($field, $value)) {
			return $this->show($field, $value);
		}

		// 字段存储名称
		$name = $field['fieldname'];

		// 字段显示名称
		$text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').$field['name'];

		// 字段提示信息
		$tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

		// 字段默认值
		$value = strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);
		
		$str = '';

		// 显示方式
		$show_type = (int)$field['setting']['option']['show_type'];

		// 表单选项
		$options = dr_format_option_array($field['setting']['option']['options']);
		if ($options) {
			foreach ($options as $v => $n) {
				$s = $v == $value ? ' checked' : '';
				$kj = '<input type="radio" name="data['.$name.']" value="'.$v.'" '.$s.' '.$field['setting']['validate']['formattr'].' />';
				$str.= '<label class="mt-radio mt-radio-outline">'.$kj.' '.$n.' <span></span> </label>';
			}
		}

		return $this->input_format($name, $text, '<div class="'.(!$show_type ? 'mt-radio-inline' : 'mt-radio-list').'">'.$str.'</div>'.$tips);
	}

    /**
     * 字段表单显示
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function show($field, $value = null) {

        $options = dr_format_option_array($field['setting']['option']['options']);

        $str = '<div class="form-control-static"> '.(isset($options[$value]) ? $options[$value] : dr_lang('未选择')).' </div>';

        return $this->input_format($field['fieldname'], $field['name'], $str);
    }
}