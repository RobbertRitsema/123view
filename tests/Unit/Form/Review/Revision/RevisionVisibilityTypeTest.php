<?php
declare(strict_types=1);

namespace DR\Review\Tests\Unit\Form\Review\Revision;

use DR\Review\Entity\Revision\RevisionVisibility;
use DR\Review\Form\Review\Revision\RevisionVisibilityType;
use DR\Review\Tests\AbstractTestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Debug\OptionsResolverIntrospector;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @coversDefaultClass \DR\Review\Form\Review\Revision\RevisionVisibilityType
 */
class RevisionVisibilityTypeTest extends AbstractTestCase
{
    private RevisionVisibilityType $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->type = new RevisionVisibilityType();
    }

    /**
     * @covers ::configureOptions
     */
    public function testConfigureOptions(): void
    {
        $resolver     = new OptionsResolver();
        $introspector = new OptionsResolverIntrospector($resolver);

        $this->type->configureOptions($resolver);

        static::assertNull($introspector->getDefault('form_id'));
        static::assertSame(RevisionVisibility::class, $introspector->getDefault('data_class'));
        static::assertSame(['string'], $introspector->getAllowedTypes('form_id'));
    }

    /**
     * @covers ::buildForm
     */
    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())->method('add')->with('visible', CheckboxType::class)->willReturnSelf();

        $this->type->buildForm($builder, ['form_id' => 123]);
    }
}
