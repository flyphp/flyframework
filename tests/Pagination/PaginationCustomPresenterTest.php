<?php

use Mockery as m;

class PaginationCustomPresenterTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testGetPageLinkWrapper()
	{
		$customPresenter = m::mock('Fly\Pagination\Presenter');
		$customPresenter->shouldReceive('getPageLinkWrapper')
			->once()
			->andReturnUsing(function($url, $page) {
				return '<a href="' . $url . '">' . $page . '</a>';
			});
		$this->assertEquals('<a href="http://flyphp.org?page=1">1</a>', $customPresenter->getPageLinkWrapper('http://flyphp.org?page=1', '1'));
	}

	public function testGetDisabledTextWrapper()
	{
		$customPresenter = m::mock('Fly\Pagination\Presenter');
		$customPresenter->shouldReceive('getDisabledTextWrapper')
			->once()
			->andReturnUsing(function($text) {
				return '<li class="bar">' . $text . '</li>';
			});
		$this->assertEquals('<li class="bar">foo</li>', $customPresenter->getDisabledTextWrapper('foo'));
	}

	public function testGetActiveTextWrapper()
	{
		$customPresenter = m::mock('Fly\Pagination\Presenter');
		$customPresenter->shouldReceive('getActiveTextWrapper')
			->once()
			->andReturnUsing(function($text) {
				return '<li class="baz">' . $text . '</li>';
			});
		$this->assertEquals('<li class="baz">bazzer</li>', $customPresenter->getActiveTextWrapper('bazzer'));
	}

}
